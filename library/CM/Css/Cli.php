<?php

class CM_Css_Cli extends CM_Cli_Runnable_Abstract {

    /**
     * @synchronized
     */
    public function iconRefresh() {
        /** @var CM_File[] $svgFileList */
        $svgFileList = array();
        foreach (CM_Bootloader::getInstance()->getModules() as $moduleName) {
            $iconPath = CM_Util::getModulePath($moduleName) . 'layout/default/resource/img/icon/';
            foreach (glob($iconPath . '*.svg') as $svgPath) {
                $svgFile = new CM_File($svgPath);
                $svgFileList[strtolower($svgFile->getFileName())] = $svgFile;
            }
        }

        if (0 === count($svgFileList)) {
            throw new CM_Exception_Invalid('Cannot process `0` icons');
        }
        $this->_getStreamOutput()->writeln('Processing ' . count($svgFileList) . ' unique icons...');

        $dirWork = CM_File::createTmpDir();
        foreach ($svgFileList as $fontFile) {
            $fontFile->copy($dirWork->getPath() . '/' . $fontFile->getFileName());
        }

        $dirBuild = new CM_File($dirWork->getPath() . '/build');
        CM_Util::exec('fontcustom',
            array('compile', $dirWork->getPath(), '--no-hash', '--font-name=icon-webfont', '--output=' . $dirBuild->getPath()));

        $cssFile = new CM_File($dirBuild->getPath() . '/icon-webfont.css');
        $less = preg_replace('/url\("(?:.*?\/)(.+?)(\??#.+?)?"\)/', 'url(urlFont("\1") + "\2")', $cssFile->read());
        CM_File::create(DIR_PUBLIC . 'static/css/library/icon.less', $less);

        foreach (glob($dirBuild->getPath() . '/icon-webfont.*') as $fontPath) {
            $fontFile = new CM_File($fontPath);
            $fontFile->rename(DIR_PUBLIC . 'static/font/' . $fontFile->getFileName());
        }

        $dirWork->delete(true);
        $this->_getStreamOutput()->writeln('Created web-font and stylesheet.');
    }

    public function emoticonRefresh() {
        $emoticonList = array();

        foreach (CM_Bootloader::getInstance()->getModules() as $namespace) {
            $emoticonPath = CM_Util::getModulePath($namespace) . 'layout/default/resource/img/emoticon/';
            $paths = glob($emoticonPath . '*');
            foreach ($paths as $path) {
                $file = new CM_File($path);
                $name = strtolower($file->getFileNameWithoutExtension());
                $emoticonList[$name] = array('name' => $name, 'fileName' => $file->getFileName());
            }
        }

        $insertList = array();
        foreach ($emoticonList as $emoticon) {
            $insertList[] = array(':' . $emoticon['name'] . ':', $emoticon['fileName']);
        }

        CM_Db_Db::insertIgnore('cm_emoticon', array('code', 'file'), $insertList);
        $this->_getStreamOutput()->writeln('Updated ' . count($insertList) . ' emoticons.');

        $this->_checkEmoticonValidity();
    }

    private function _checkEmoticonValidity() {
        $paging = new CM_Paging_Emoticon_All();
        $codes = array();
        foreach ($paging as $emoticon) {
            if (false !== array_search('', $emoticon['codes'])) {
                $this->_getStreamError()->writeln('WARNING: Empty emoticon with ID `' . $emoticon['id'] . '`.');
                return;
            }
            $codes = array_merge($codes, $emoticon['codes']);
        }
        for ($i = 0; $i < count($codes); $i++) {
            for ($j = $i + 1; $j < count($codes); $j++) {
                if (false !== strpos($codes[$i], $codes[$j]) || false !== strpos($codes[$j], $codes[$i])) {
                    $this->_getStreamError()->writeln('WARNING: Emoticon intersection: `' . $codes[$i] . '` <-> `' . $codes[$j] . '`.');
                }
            }
        }
    }

    public static function getPackageName() {
        return 'css';
    }
}
