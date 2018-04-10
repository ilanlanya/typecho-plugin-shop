<?php

class Shop_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 返回错误信息json
     * @param  [type] $error [description]
     * @return [type]        [description]
     */
    public function errorJson($error)
    {

        return json_encode(compact('error'));
    }

    /**
     * 创建临时文件
     * @return [type] [description]
     */
    public function createTmpFile($tmp_path = '.' . __TYPECHO_THEME_DIR__ . '/cache.zip')
    {
        try {
            //尝试创建文件
            $fp = fopen($tmp_path, "w");
            fclose($fp);
        
        } catch (Exception $e) {
            //失败则返回false
            return false;
        }

        //返回文件路径
        return $tmp_path;
    }

    /**
     * 准备下载
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    public function prepareDownload($url)
    {
        //设置超时时间
        set_time_limit(0);
        //获取远程文件资源的大小
        if (!$file_size = $this->remote_filesize($url)) {
            return $this->errorJson('文件可能不存在，请访问此链接手动下载 ' . $url);
        }
        //创建临时文件
        if(!$tmp_path = $this->createTmpFile()) {
            return $this->errorJson('文件创建失败');
        }
        return json_encode(compact('url', 'tmp_path', 'file_size'));
    }

    /**
     * 开始下载
     * @param  [type] $tmp_path [description]
     * @param  [type] $url      [description]
     * @return [type]           [description]
     */
    public function startDownload($tmp_path, $url)
    {
        //设置超时时间
        set_time_limit(0);
        //取消Session文件锁定
        session_write_close();
        try {
            //创建文件（如果不存在）
            touch($tmp_path);
            //打开远程文件
            if ($fp = fopen($url, 'rb')) {
                //打开本地文件
                if (!$download_fp = fopen($tmp_path, 'wb')) {
                    return $this->errorJson('文件打开失败');
                }
                while (!feof($fp)) {
                    if (!file_exists($tmp_path)) {
                        // 如果临时文件被删除就取消下载
                        fclose($download_fp);
                        return $this->errorJson('文件下载已经取消');
                    }
                    //读取远程文件内容
                    fwrite($download_fp, fread($fp, 1024 * 8), 1024 * 8);
                }
                fclose($download_fp);
                fclose($fp);
            } else {
                //远程文件打开失败
                return $this->errorJson('远程文件可能不存在');
            }
        } catch (Exception $e) {
            //删除文件
            remove($tmp_path);
            return $this->errorJson('下载文件失败');
        }
        return json_encode(compact('tmp_path'));
    }

    /**
     * 轮询已下载大小
     * @param  [type] $tmp_path [description]
     * @return [type]           [description]
     */
    public function getFileSize($tmp_path)
    {
        session_write_close();
        if (file_exists($tmp_path)) {
            return json_encode(['size' => filesize($tmp_path)]);
        } else {
            return json_encode(['size' => -1]);
        }
    }

    /**
     * 解压临时文件
     * @param  [type] $tmp_path [description]
     * @param  [type] $name     [description]
     * @return [type]           [description]
     */
    public function zipFile($tmp_path, $name)
    {
        //替换特殊字符
        $name = str_replace('-space-', ' ', $name);
        //新压缩对象
        $zip  = new ZipArchive();
        //打开压缩文件
        if ($zip->open($tmp_path) !== true) {
            return $this->errorJson('压缩文件打开失败');
        }
        //定位index.php文件位置
        $index = $zip->locateName('index.php', ZIPARCHIVE::FL_NOCASE | ZIPARCHIVE::FL_NODIR);
        if ($index || 0 === $index) {
            $indexpaths = explode('/', $zip->getNameIndex($index));
            $dirs = count($indexpaths);
            //压缩深度
            if ($dirs > 2) {
                return $this->errorJson('压缩路径太深，无法正常安装');
            }
            //获取index文件内容
            $contents = $zip->getFromIndex($index);
            //判断文件是否为主题文件
            if ($this->isTheme($contents)) {
                //确定解压路径
                if (2 == $dirs) {
                    $path = '.' . __TYPECHO_THEME_DIR__ . '/';
                } else {
                    $path = '.' . __TYPECHO_THEME_DIR__ . '/' . $name . '/';
                }
                //解压缩文件
                if (!$zip->extractTo($path)) {
                    return $this->errorJson('文件解压失败');
                }
                $zip->close();
                //删除临时文件
                unlink($tmp_path);

                if (2 == $dirs) {
                    try {
                        rename($path . $indexpaths[0], $path . $name);
                        return json_encode(['success' => '文件解压成功']);
                    } catch (Exception $e) {
                        return json_encode(['error' => '文件重命名失败，文件可能已存在']);
                    }
                }
            } else {
                return $this->errorJson('目标资源不是主题资源');
            }
        }
    }

    /**
     * 判断上传文件是否为模板
     * @param type $contents
     * @return boolean
     */
    public function isTheme($contents)
    {
        $info = $this->parseInfo($contents);
        if ($info['title'] !== "" && !empty($info['version']) && !empty($info['author'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 解析主题下的index.php文件
     * @param  [type] $themeFile [description]
     * @return [type]            [description]
     */
    public function parseInfo($themeFile)
    {
        $tokens  = token_get_all($themeFile);
        $isDoc   = false;
        $isClass = false;

        /** 初始信息 */
        $info = array(
            'name'        => '',
            'description' => '',
            'title'       => '',
            'author'      => '',
            'homepage'    => '',
            'version'     => '',
            'dependence'  => '',
        );

        $map = array(
            'package'    => 'title',
            'author'     => 'author',
            'link'       => 'homepage',
            'dependence' => 'dependence',
            'version'    => 'version',
        );

        foreach ($tokens as $token) {
            /** 获取doc comment */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {
                /** 分行读取 */
                $described = false;
                $lines     = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } else if ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line                = trim(substr($line, 1));
                            $args                = explode(' ', $line);
                            $key                 = array_shift($args);

                            if (isset($map[$key])) {
                                $info[$map[$key]] = trim(implode(' ', $args));
                            }
                        }
                    }
                }
                $isDoc = true;
            }
            if (!$isClass && is_array($token) && T_CLASS == $token[0]) {
                $isClass = true;
            }
            if ($isClass && is_array($token) && T_STRING == $token[0]) {
                $name         = split('_', $token[1]);
                $info['name'] = $name[0];
                break;
            }
        }
        return $info;
    }

    /**
     * 获取远程文件的大小
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    public function remote_filesize($url)
    {
        stream_context_set_default(
            array(
                'http' => array(
                    'method' => 'HEAD',
                ),
            )
        );

        for($i = 0; $i < 3; $i++) {
            if ($header = get_headers($url, true)) {
                return isset($header['Content-Length']) ? $header['Content-Length'] : false;
            }
            sleep(1);            
        }
        return false;
    }

    public function action()
    {
        if ($this->request->is('do=preparedownload')) {
            echo $this->prepareDownload($this->request->url, $this->request->name);
        } elseif ($this->request->is('do=startdownload')) {
            echo $this->startDownload($this->request->tmp_path, $this->request->url);
        } elseif ($this->request->is('do=getfilesize')) {
            echo $this->getFileSize($this->request->tmp_path);
        } elseif ($this->request->is('do=zip')) {
            echo $this->zipFile($this->request->tmp_path, $this->request->name);
        }
    }
}
