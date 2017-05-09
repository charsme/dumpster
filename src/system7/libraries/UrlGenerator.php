<?php
use Illuminate\Database\Capsule\Manager as DB;

class UrlGenerator
{
    public $_coll_domain   = [];
    public $_coll_category = [];

    private function _getCatTree($domain_id)
    {
        $category      = DB::select("SELECT * FROM ".DB::getTablePrefix()."rubrics WHERE rubrics_domain_id = :domain_id AND rubrics_invalid = :invalid ORDER BY rubrics_parent ASC", ['domain_id' => $domain_id, 'invalid' => '0']);

        $tmp = [];
        $result = [];
        foreach ($category as $k => $v) {
            $v = (array) $v;
            $v['child'] = [];
            if ($v['rubrics_parent'] == '' || $v['rubrics_parent'] == '0') {
                $result[$v['rubrics_id']] = $v;
            } else {
                if (isset($result[$v['rubrics_parent']])) {
                    $result[$v['rubrics_parent']]['child'][$v['rubrics_id']] = $v;
                } else {
                    $tmp[$v['rubrics_parent']][$v['rubrics_id']] = $v;
                }
            }
        }

        $max_loop = 5;
        $loop = 1;
        while (count($tmp) && $loop < $max_loop) {
            $result = $this->_catSetRecursive($result, $tmp);
            $loop++;
        }

        return $result;
    }

    private function _catSetRecursive($result, &$tmp)
    {
        if (is_array($result)) {
            foreach ($result as $k => $v) {
                if (isset($tmp[$k])) {
                    $result[$k]['child'] = array_merge($result[$k]['child'], $tmp[$k]);
                    unset($tmp[$k]);
                }
                if (isset($result[$k]['child']) && is_array($result[$k]['child']) && count($result[$k]['child'])) {
                    $result[$k]['child'] = self::setRecursive($result[$k]['child'], $tmp);
                }
            }
        }
        return $result;
    }

    private function _getCat($domain)
    {
        if (isset($domain->domain_id) && $domain->domain_id) {
            $row      = $this->_getCatTree($domain->domain_id);
            $data     = [];
            foreach ($row as $k => $grandpa) {
                $data['id_to_name'][$grandpa['rubrics_id']]               = $grandpa['rubrics_name'];
                $data['name_to_id'][$grandpa['rubrics_name']]             = $grandpa['rubrics_id'];
                $data['url_to_id'][$grandpa['rubrics_url']]               = $grandpa['rubrics_id'];
                $data['id_to_url'][$grandpa['rubrics_id']]                = $grandpa['rubrics_url'];
                $data['grandparent_name_to_id'][$grandpa['rubrics_name']] = $grandpa['rubrics_id'];
                $data['grandparent_id_to_name'][$grandpa['rubrics_id']]   = $grandpa['rubrics_name'];
                $data['grandparent_url_to_id'][$grandpa['rubrics_url']]   = $grandpa['rubrics_id'];
                $data['grandparent_id_to_url'][$grandpa['rubrics_id']]    = $grandpa['rubrics_url'];
                if (is_array($grandpa['child']) && count($grandpa['child'])) {
                    foreach ($grandpa['child'] as $kf => $parent) {
                        $data['id_to_name'][$parent['rubrics_id']]             = $parent['rubrics_name'];
                        $data['name_to_id'][$parent['rubrics_name']]           = $parent['rubrics_id'];
                        $data['url_to_id'][$parent['rubrics_url']]             = $parent['rubrics_id'];
                        $data['id_to_url'][$parent['rubrics_id']]              = $parent['rubrics_url'];
                        $data['child_id_to_parent'][$parent['rubrics_id']]     = $grandpa['rubrics_name'];
                        $data['child_id_to_parenturl'][$parent['rubrics_id']]  = $grandpa['rubrics_url'];
                        $data['child_name_to_parent'][$parent['rubrics_name']] = $grandpa['rubrics_name'];
                        $data['child_url_to_parent'][$parent['rubrics_url']]   = $grandpa['rubrics_url'];
                        $data['child_url_to_parentid'][$parent['rubrics_url']] = $grandpa['rubrics_id'];
                        if (is_array($parent['child']) && count($parent['child'])) {
                            foreach ($parent['child'] as $kc => $child) {
                                $data['id_to_name'][$child['rubrics_id']]             = $child['rubrics_name'];
                                $data['name_to_id'][$child['rubrics_name']]           = $child['rubrics_id'];
                                $data['url_to_id'][$child['rubrics_url']]             = $child['rubrics_id'];
                                $data['id_to_url'][$child['rubrics_id']]              = $child['rubrics_url'];
                                $data['child_id_to_parent'][$child['rubrics_id']]     = $grandpa['rubrics_name'];
                                $data['child_id_to_parenturl'][$child['rubrics_id']]  = $grandpa['rubrics_url'];
                                $data['child_name_to_parent'][$child['rubrics_name']] = $grandpa['rubrics_name'];
                                $data['child_url_to_parent'][$child['rubrics_url']]   = $grandpa['rubrics_url'];
                                $data['child_url_to_parentid'][$child['rubrics_url']] = $grandpa['rubrics_id'];
                            }
                        }
                    }
                }
            }

            return $data;
        }
        return [];
    }

    private function _getParentCategory($news, $cat_id)
    {
        $id_cat  = $cat_id;
        $cat     = isset($this->_coll_category[$news['news_domain_id']]['id_to_name'][$cat_id]) ? $this->_coll_category[$news['news_domain_id']]['id_to_name'][$cat_id] : '';
        $cat_url = isset($this->_coll_category[$news['news_domain_id']]['id_to_url'][$cat_id]) ? $this->_coll_category[$news['news_domain_id']]['id_to_url'][$cat_id] : '';
        if (isset($this->_coll_category[$news['news_domain_id']]['child_url_to_parentid'][$cat_url])) {
            $parent_id  = $this->_coll_category[$news['news_domain_id']]['child_url_to_parentid'][$cat_url];
            $parent     = isset($this->_coll_category[$news['news_domain_id']]['id_to_name'][$parent_id]) ? $this->_coll_category[$news['news_domain_id']]['id_to_name'][$parent_id] : '';
            $parent_url = isset($this->_coll_category[$news['news_domain_id']]['id_to_url'][$parent_id]) ? $this->_coll_category[$news['news_domain_id']]['id_to_url'][$parent_id] : '';
        } else {
            $parent     = $cat;
            $parent_id  = $cat_id;
            $parent_url = $cat_url;
        }

        return array(
            'name' => $parent, 'id' => $parent_id, 'url' => $parent_url, 'cat_id' => $id_cat, 'cat_name' => $cat
        );
    }

    public function genUrl($url)
    {
        if (defined('ENVIRONMENT')) {
            if (ENVIRONMENT == 'development') {
                $url   = str_replace(array("http://", 'https://'), '', $url);
                $tmp   = explode('.', $url);
                $first = current($tmp);
                switch (true) {
                    case ($first == 'www' || $first == 'dev'):
                        $first = 'dev';
                        break;
                    case intval($first):
                        $first = $first;
                        break;
                    default:
                        $first .= '-dev';
                        break;
                }
                $tmp[0] = $first;
                $url    = 'http://' . implode('.', $tmp);
            }
        }

        return $url;
    }

    public function generate($news, $with_domain = false)
    {
        if (!isset($this->_coll_domain[$news['news_domain_id']])) {
            $domain = DB::select("SELECT * FROM ".DB::getTablePrefix()."domains WHERE domain_id = :domain_id", ['domain_id' => $news['news_domain_id']]);
            $this->_coll_domain[$news['news_domain_id']] = (isset($domain[0])) ? $domain[0] : false;
        }
        if (!$this->_coll_domain[$news['news_domain_id']]) {
            return false;
        }

        if (!isset($this->_coll_category[$news['news_domain_id']])) {
            $this->_coll_category[$news['news_domain_id']] = $this->_getCat($this->_coll_domain[$news['news_domain_id']]);
        }

        if (!is_array($news['news_category'])) {
            $id_category = json_decode($news['news_category'], true);
        }
        $id_category           = (is_array($id_category) && count($id_category) > 0) ? $id_category[0] : false;
        $url_cat               = isset($this->_coll_category[$news['news_domain_id']]['id_to_url'][$id_category]) ? $this->_coll_category[$news['news_domain_id']]['id_to_url'][$id_category] : false;
        $news['category_name'] = isset($this->_coll_category[$news['news_domain_id']]['id_to_name'][$id_category]) ? $this->_coll_category[$news['news_domain_id']]['id_to_name'][$id_category] : '';
        $parent                = $this->_getParentCategory($news, $id_category);
        $news['news_base_url'] = $this->genUrl($this->_coll_domain[$news['news_domain_id']]->domain_url);

        $parent_and_child = $url_cat;
        if ($parent_and_child != $parent['url']) {
            $parent_and_child = $parent['url'].'/'.$parent_and_child;
        }
        $type  = array('news', 'photonews', 'video');
        $url_format = (is_array($this->_coll_domain[$news['news_domain_id']]->url_format)) ? $this->_coll_domain[$news['news_domain_id']]->url_format : json_decode($this->_coll_domain[$news['news_domain_id']]->url_format, true);
        $url_search         = array(
            '[DOMAIN_URL]',
            '[CATEGORY_PARENT]',
            '[CATEGORY_PARENT_AND_CHILD]',
            '[CATEGORY]',
            '[NEWS_URL]',
            '[NEWS_ID]'
        );
        $url_replace        = array(
            $this->genUrl($this->_coll_domain[$news['news_domain_id']]->domain_url),
            $parent['url'],
            $parent_and_child,
            $url_cat,
            $news['news_url'],
            $news['news_id'],
        );
        if (isset($url_format[$type[$news['news_type']]]) && $url_format[$type[$news['news_type']]]) {
            $news['news_url_full'] = str_replace($url_search, $url_replace, $url_format[$type[$news['news_type']]]);
        } else {
            $urlPhoto = '';
            if ($news['news_type'] == '1') {
                $urlPhoto = "photo/";
            } elseif ($news['news_type'] == '2') {
                $urlPhoto = "video/";
            }
            $news['news_url_full'] = $this->genUrl($this->_coll_domain[$news['news_domain_id']]->domain_url) . $urlPhoto . $parent['url'] . '/' . $news['news_url'] . '.html';
        }
        if (isset($url_format['category']) && $url_format['category']) {
            $news['category_url'] = str_replace($url_search, $url_replace, $url_format['category']);
        } else {
            $news['category_url'] = $this->genUrl($this->_coll_domain[$news['news_domain_id']]->domain_url) . $url_cat . '/' ;
        }
        if ($with_domain) {
            $news['domain'] = (array) $this->_coll_domain[$news['news_domain_id']];
        }
        return $news;
    }
}
