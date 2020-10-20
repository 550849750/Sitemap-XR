<?php namespace Phpcmf\Model\Sitemap;

use think\facade\Db;

class Sitemap extends \Phpcmf\Model
{


    private $zzconfig;

    // 配置信息
    public function getConfig()
    {

        if ($this->zzconfig) {
            return $this->zzconfig;
        }

        if (is_file(WRITEPATH . 'config/sitemap.php')) {
            $this->zzconfig = require WRITEPATH . 'config/sitemap.php';
            return $this->zzconfig;
        }

        return [];
    }

    // 配置信息
    public function setConfig($data)
    {

        \Phpcmf\Service::L('Config')->file(WRITEPATH . 'config/sitemap.php', '站长配置文件', 32)->to_require($data);

    }


    /***************************自定义部分开始**************************/


    /**
     * 取得主表和tag表的游标
     * @param string $siteid 站点id
     * @param string $module 模块名称
     * @return array|string
     */
    public function get_cursors(string $siteid, string $module)
    {
        if (!$this->is_table_exists($siteid . '_' . $module)) {
            return '';
        }
        //取得表名
        $main_table = \Phpcmf\Service::M()->dbprefix($siteid . '_' . $module);
        $tag_table = \Phpcmf\Service::M()->dbprefix($siteid . '_tag');
        $main_cursor = Db::table($main_table)->field('url')->cursor();
        $tag_cursor = Db::table($tag_table)->field('code')->cursor();
        return [$main_cursor, $tag_cursor];
    }




    /***************************自定义部分结束**************************/


    // 网站地图
    public function sitemap_xml()
    {

        $module = \Phpcmf\Service::L('input')->get('mid');

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        $xml .= '<urlset>' . PHP_EOL;
        $config = $this->getConfig();
        if ($config['sitemap']) {
            // 判断站点id
            $site_domain = []; // 全网域名对应的站点id
            if (is_file(WRITEPATH . 'config/domain_site.php')) {
                $site_domain = require WRITEPATH . 'config/domain_site.php';
            }
            $siteid = max(1, intval($site_domain[DOMAIN_NAME]));
            // 显示数量
            $limit = intval($config['sitemap_limit']);
            !$limit && $limit = 1000;

            if ($module) {
                // 单独模块
                $data = $this->_sitemap_module_data($siteid, $module, $limit);
                if ($data) {
                    foreach ($data as $t) {
                        $xml .= $t['xml'] . PHP_EOL;
                    }
                }
            } else {
                // 全站模块
                $data = [];
                foreach ($config['sitemap'] as $mid => $t) {
                    $my = $this->_sitemap_module_data($siteid, $mid, $limit);
                    $my && $data = array_merge($data, $my);
                }
                if ($data) {
                    usort($data, function ($a, $b) {
                        if ($a['time'] == $b['time'])
                            return 0;
                        return ($a['time'] > $b['time']) ? -1 : 1;
                    });
                    foreach ($data as $t) {
                        $xml .= $t['xml'] . PHP_EOL;
                    }
                }
            }

        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }


    // 网站地图
    public function sitemap_txt()
    {

        $module = \Phpcmf\Service::L('input')->get('mid');

        /*$xml = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
        $xml.= '<urlset>'.PHP_EOL;*/
        $xml = '';
        $config = $this->getConfig();
        if ($config['sitemap']) {
            // 判断站点id
            $site_domain = []; // 全网域名对应的站点id
            if (is_file(WRITEPATH . 'config/domain_site.php')) {
                $site_domain = require WRITEPATH . 'config/domain_site.php';
            }
            $siteid = max(1, intval($site_domain[DOMAIN_NAME]));
            // 显示数量
            $limit = intval($config['sitemap_limit']);
            !$limit && $limit = 1000;

            if ($module) {
                // 单独模块
                $data = $this->_sitemap_module_data($siteid, $module, $limit);
                if ($data) {
                    foreach ($data as $t) {
                        $xml .= $t['txt'] . PHP_EOL;
                    }
                }
            } else {
                // 全站模块
                $data = [];
                foreach ($config['sitemap'] as $mid => $t) {
                    $my = $this->_sitemap_module_data($siteid, $mid, $limit);
                    $my && $data = array_merge($data, $my);
                }
                if ($data) {
                    usort($data, function ($a, $b) {
                        if ($a['time'] == $b['time'])
                            return 0;
                        return ($a['time'] > $b['time']) ? -1 : 1;
                    });
                    foreach ($data as $t) {
                        $xml .= $t['txt'] . PHP_EOL;
                    }
                }
            }

        }

        //$xml.= '</urlset>'.PHP_EOL;

        return $xml;
    }

    // 模块内容生成
    private function _sitemap_module_data($siteid, $mid, $limit)
    {

        if (!$this->is_table_exists($siteid . '_' . $mid)) {
            return '';
        }

        $data = [];
        $config = $this->getConfig();
        $db = $this->db->table($siteid . '_' . $mid)->select('url,updatetime');
        if ($config['where'][$mid]) {
            $db->where($config['where'][$mid]);
        }
        $query = $db->orderBy('updatetime desc')->limit($limit)->get();
        if ($query) {
            $rows = $query->getResultArray();
            if ($rows) {
                foreach ($rows as $t) {
                    $xml = '';
                    $xml .= '    <url>' . PHP_EOL;
                    $xml .= '        <loc>' . htmlspecialchars(dr_url_prefix($t['url'], $mid, $siteid)) . '</loc>' . PHP_EOL;
                    $xml .= '        <lastmod>' . date('Y-m-d', $t['updatetime']) . '</lastmod>' . PHP_EOL;
                    $xml .= '        <changefreq>daily</changefreq>' . PHP_EOL;
                    $xml .= '        <priority>1.0</priority>' . PHP_EOL;
                    $xml .= '    </url>' . PHP_EOL;

                    $data[] = [
                        'txt' => urldecode(dr_url_prefix($t['url'], $mid, $siteid)),
                        'xml' => $xml,
                        'time' => $t['updatetime'],
                    ];
                }
            }
        }

        return $data;
    }

}