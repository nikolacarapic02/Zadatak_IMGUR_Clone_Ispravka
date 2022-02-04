<?php

namespace app\models;

use app\core\lib\Test;
use app\core\lib\Model;
use app\core\Application;

class Banner extends Model
{
    private array $banners = [];
    private Test $test;
    public static $model;

    public function __construct()
    {       
        self::$model = $this;
        parent::__construct(self::$model);
        $this->test = new Test();
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getAllBanners()
    {
        return Application::$app->db->getAllBanners();
    }

    public function getBanner($banner_id)
    {
        return Application::$app->db->getSingleBanner($banner_id);
    }

    public function createBanner(array $attributes)
    {

    }

    public function generateBanners()
    {
        $this->banners = $this->getAllBanners();
        $positions = [];
        $result = [];
        $uri = Application::$app->request->getPath();

        if($uri === '/')
        {
            $positions = ['header', 'footer', 'left', 'right', 'content'];
        }
        else if($uri === '/photos' || $uri === '/galleries')
        {
            $positions = ['header', 'footer', 'left', 'right'];
        }
        else if($uri === '/user_profile')
        {
            $positions = ['header', 'footer', 'content'];
        }
        else if($uri === '/moderator_logging' || $uri === '/banner_analytics')
        {
            $positions = [];
        }
        else
        {
            $positions = ['header', 'footer'];
        }

        if(!empty($this->banners))
        {
            for($i = 0; $i < count($positions); $i++)
            {
                $key = rand(0, (count($this->banners)-1));

                if($positions[$i] === 'left' || $positions[$i] === 'right')
                {
                    for($j = 0; $j < rand(2,4); $j++)
                    {
                        $key = rand(0, (count($this->banners)-1));

                        $this->banners[$key]['position'] = $positions[$i];
                        array_push($result, $this->banners[$key]);
                    }
                }
                else if($positions[$i] === 'content')
                {
                    for($j = 0; $j < 2; $j++)
                    {
                        $key = rand(0, (count($this->banners)-1));

                        $this->banners[$key]['position'] = $positions[$i];
                        array_push($result, $this->banners[$key]);
                    }
                }
                else
                {
                    $this->banners[$key]['position'] = $positions[$i];
                    array_push($result, $this->banners[$key]);
                }
            }

            $this->test->testingAdViews($result);

            return $result;
        }
    }

    public function getBannersAnalytics($page)
    {
        return Application::$app->db->getAnalyticsOfBanners($page);
    }

    public function filterBannersAnalytics()
    {

    }
}