<?php

namespace app\core\lib;

use app\core\Application;

class Test
{
    public function testingAdViews(array $ads)
    {
        if(!empty($ads))
        {
            for($i = 0; $i < count($ads); $i++)
            {
                Application::$app->db->addToBannerTesting($ads[$i]['id']);
                Application::$app->db->countViewsOfBanner($ads[$i]['id'], $ads[$i]['position']);
            }
        }
    }

    public function testingAdClicks(array $ads)
    {
        $data = Application::$app->request->getData();

        if(key_exists('banner', $data))
        {
            for($i = 0; $i < count($ads); $i++)
            {
                if($ads[$i]['id'] === $data['banner'])
                {
                    Application::$app->db->countClicksOfBanner($ads[$i]['id'], $ads[$i]['position']);
                }
            }
        }
    }
}