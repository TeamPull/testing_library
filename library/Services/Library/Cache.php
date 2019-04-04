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
        $this->clearNginxProxyCache();
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
            if (is_dir("$dir/$file")) {
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

    /**
     * Test helper to clear nginx cache
     */
    protected function clearNginxProxyCache()
    {
        $facts = new \OxidEsales\Facts\Facts();
        if (!$facts->isEnterprise()) {
            return;
        }

        $testModulePaths = $this->getTestConfig()->getPartialModulePaths();
        if (in_array('oe/nginx', $testModulePaths)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getTestConfig()->getShopUrl() . 'nginx-clear-cache');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 31);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "OXID-TEST");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "all=true");
            $result = curl_exec($ch);

            $databaseMetaDataHandler = oxNew(\OxidEsales\Eshop\Core\DbMetaDataHandler::class);
            if ($databaseMetaDataHandler->tableExists(\OxidEsales\NginxModule\Cache\Backend::NGINX_HASH_TABLE)) {
                $query = 'TRUNCATE table ' . \OxidEsales\NginxModule\Cache\Backend::NGINX_HASH_TABLE;
                \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($query);
            }
        }
    }
}
