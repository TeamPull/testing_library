<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace OxidEsales\TestingLibrary\Services\Library;



/**
 * Class used for uploading files in services.
 */
class Cache
{
    /**
     * Clears cache backend.
     */
    public function clearCacheBackend()
    {
        if (class_exists('\OxidEsales\EshopEnterprise\Core\Cache\Generic\Cache')) {
            $oCache = oxNew(\OxidEsales\Eshop\Core\Cache\Generic\Cache::class);
            $oCache->flush();
        }
    }

    /**
     * Clears reverse proxy cache.
     */
    public function clearReverseProxyCache()
    {
        if (class_exists('\OxidEsales\VarnishModule\ReverseProxy\ReverseProxyBackend', false)) {
            $oReverseProxy = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\VarnishModule\ReverseProxy\ReverseProxyBackend::class);
            $oReverseProxy->setFlush();
            $oReverseProxy->execute();
        }
        if (class_exists('\OxidEsales\EshopEnterprise\Core\Cache\ReverseProxy\ReverseProxyBackend', false)) {
            $oReverseProxy = oxNew(\OxidEsales\EshopEnterprise\Core\Cache\ReverseProxy\ReverseProxyBackend::class);
            $oReverseProxy->setFlush();
            $oReverseProxy->execute();
        }
        if (class_exists('\OxidEsales\NginxModule\Cache\Backend', false)) {
            $invalidatorRuleSet = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\NginxModule\Cache\InvalidatorRuleSet::class);
            $invalidatorRuleSet->addRule(oxNew(\OxidEsales\NginxModule\Cache\InvalidatorRule\All::class, 'flush'));
            $cache = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\NginxModule\Cache\Backend::class);
            $cache->execute();
        }
    }

    /**
     * Clears temporary directory.
     */
    public function clearTemporaryDirectory()
    {
        if ($sCompileDir = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Core\ConfigFile::class)->getVar('sCompileDir')) {
            CliExecutor::executeCommand("sudo chmod 777 -R $sCompileDir");
            $this->removeTemporaryDirectory($sCompileDir, false);
        }
    }

    /**
     * Delete all files and dirs recursively
     *
     * @param string $dir       Directory to delete
     * @param bool   $rmBaseDir Keep target directory
     */
    private function removeTemporaryDirectory($dir, $rmBaseDir = false)
    {
        $itemsToIgnore = array('.', '..', '.htaccess');

        $files = array_diff(scandir($dir), $itemsToIgnore);
        foreach ($files as $file) {
            if (is_dir("$dir/$file") && ('smarty' !== $file)) {
                $this->removeTemporaryDirectory(
                    "$dir/$file",
                    $file == 'smarty' ? $rmBaseDir : true
                );
            } else {
                @unlink("$dir/$file");
            }
        }
        if ($rmBaseDir) {
            @rmdir($dir);
        }
    }
}
