<?php

namespace App\Helpers;

class PageHelper
{
    public $curr_page=0;
    public $page_size=0;
    public $total_num=0;
    public $total_page= 0 ;
    public $page_limit = "";
    const MAX_PAGE_SIZE = 500;

    public function __construct($curr_page,$page_size,$count=0)
    {
        $this->init($curr_page,$page_size,$count);
    }

    public function init($curr_page,$page_size,$count)
    {
        $curr_page = intval($curr_page);
        $page_size = intval($page_size);
        if(empty($curr_page))
        {
            $curr_page =1;
        }
        if($page_size<=0)
        {
            $page_size =10;
        }
        if($page_size>=500)
        {
            $page_size = self::MAX_PAGE_SIZE ;
        }
        $this->curr_page = $curr_page;
        $this->page_size = $page_size;
        $page_limit = $this->get_limit($curr_page,$page_size);
        $this->page_limit  = $page_limit;
        if($count)
        {
            $this->set_count($count);
        }
    }

    public function set_count($count)
    {
        $total_page = ceil(ceil($count/$this->page_size));
        $this->total_num = $count;
        $this->total_page = $total_page;
    }

    public function get_limit()
    {
        $curr_page = $this->curr_page;
        $page_size = $this->page_size;
        $curr_page = intval($curr_page);
        if(empty($curr_page) || $curr_page <= 1)
        {
            return " limit {$page_size}";
        }
        else
        {
            $start = ((int)$curr_page - 1) * $page_size;
            return " limit {$start},{$page_size}";
        }
    }

}
