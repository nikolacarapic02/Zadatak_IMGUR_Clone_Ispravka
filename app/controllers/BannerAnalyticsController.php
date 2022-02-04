<?php 

namespace app\controllers;

use Twig\Environment;
use app\models\Banner;
use app\core\lib\Controller;

class BannerAnalyticsController extends Controller
{
    protected Environment $view;
    protected Banner $banner;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->banner = new Banner();
    }

    public function index()
    {
        return $this->view->render('banner_analytics.html', [
            'title' => 'Banner Analytics',
            'banners' => $this->banner->getBannersAnalytics($this->banner->getPage()),
            'numOfPages' => $this->banner->numOfPages($this->banner),
            'page' => $this->banner->getPage(),
            'pageNum' => $this->banner->getPage(),
        ]);
    }

    public function create()
    {

    }

    public function update()
    {

    }
}