<?php namespace Phpcmf\Controllers;


class Home extends \Phpcmf\Common
{

    public function index()
    {
        header('Content-Type: text/plain');
        echo \Phpcmf\Service::M('sitemap', 'sitemap')->sitemap_txt();
        exit;
    }

    public function xml()
    {
        header('Content-Type: text/xml');
        echo \Phpcmf\Service::M('sitemap', 'sitemap')->sitemap_xml();
        exit;
    }


    /**********************自定义部分**************************/

    /**
     * xml文件生成器
     */
    public function xml_generator()
    {
        $xmls = $this->get_all_xml();
        $i = 1;
        foreach ($xmls as $xml) {
            $file = 'sitemap/sitemap' . $i . '.xml';
            $base = ROOTPATH;
            defined('IS_MOBILE') && IS_MOBILE && $base .= 'mobile/';
            $this->write_file($base . $file, $xml) && $i++;
        }
        return '写入完成';
    }

    /**
     * 写入文件
     * @param string $file 文件全名
     * @param string $content 内容
     * @return bool|int
     */
    protected function write_file(string $file, string $content)
    {
        !is_dir(dirname($file)) && dr_mkdirs(dirname($file));
        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();

        return @file_put_contents($file, $this->charsetToUTF8($content), LOCK_EX);
    }

    /**
     * 生成xml文件内容数组
     * @return array
     */
    protected function get_all_xml()
    {
        $module = 'fishing';
        // 判断站点id
        $site_domain = []; // 全网域名对应的站点id
        if (is_file(WRITEPATH . 'config/domain_site.php')) {
            $site_domain = require WRITEPATH . 'config/domain_site.php';
        }
        $siteid = max(1, intval($site_domain[DOMAIN_NAME]));
        // 显示数量
        $limit = 5000;
        list($main_cursor, $tag_cursor) = \Phpcmf\Service::M('sitemap', 'sitemap')->get_cursors($siteid, $module);
        //每$limit条记录保存为它的一个元素
        $items = [];
        $item = '';
        $i = 0;
        foreach ($main_cursor as $main) {
            if ($i >= $limit) {
                $i = 0;
                $items[] = $item;
                $item = '';
            }
            if ($main['url']) {
                $item .= $this->get_xml_paragraph($main['url']);
                $i++;
            }
        }
        foreach ($tag_cursor as $tag) {
            if ($i >= $limit) {
                $i = 0;
                $items[] = $item;
                $item = '';
            }
            if ($tag['code']) {
                $url = '/title/' . $tag['code'] . '.html';
                $item .= $this->get_xml_paragraph($url);
                $i++;
            }
        }
        //将最后一个xml加入数组
        $items[] = $item;
        //生成xml文件内容
        return $this->get_sitemap_xml($items);

    }


    /**
     * 取一段xml内容
     * @param string $path 相对路径url
     * @param string $module 模块名
     * @param string $siteid 站点ID
     * @return string
     * @throws \Exception
     */
    protected function get_xml_paragraph(string $path)
    {
        $item = '';
        $time = time() - random_int(1, 999999);
        //网站域名
        $base_url = defined('IS_MOBILE') && IS_MOBILE ? SITE_MURL : SITE_URL;
        $url = rtrim($base_url, '/') . '/' . ltrim($path, '/');
        $xml = '';
        $xml .= '    <url>' . PHP_EOL;
        $xml .= '        <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
        $xml .= '        <lastmod>' . date('Y-m-d H:i:s', $time) . '</lastmod>' . PHP_EOL;
        $xml .= '        <changefreq>daily</changefreq>' . PHP_EOL;
        $xml .= '        <priority>1.0</priority>' . PHP_EOL;
        $xml .= '    </url>' . PHP_EOL;
        $item .= $xml . PHP_EOL;
        return $item;
    }

    /**
     * 生成xml文件内容数组
     * @param array $items 数据集(一维数组)
     * @return array
     */
    protected function get_sitemap_xml($items)
    {
        $xmls = [];
        foreach ($items as $item) {
            if ($item) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
                $xml .= '<urlset>' . PHP_EOL;
                $xml .= $item;
                $xml .= '</urlset>' . PHP_EOL;
                $xmls[] = $xml;
            }
        }
        return $xmls;
    }


    /**
     * 将非UTF-8字符集的编码转为UTF-8
     *
     * @param mixed $mixed 源数据
     *
     * @return mixed utf-8格式数据
     */
    protected function charsetToUTF8($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                if (is_array($v)) {
                    $mixed[$k] = charsetToUTF8($v);
                } else {
                    $encode = mb_detect_encoding($v, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                    if ($encode == 'EUC-CN') {
                        $mixed[$k] = iconv('GBK', 'UTF-8', $v);
                    }
                }
            }
        } else {
            $encode = mb_detect_encoding($mixed, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
            if ($encode == 'EUC-CN') {
                $mixed = iconv('GBK', 'UTF-8', $mixed);
            }
        }
        return $mixed;

    }

}
