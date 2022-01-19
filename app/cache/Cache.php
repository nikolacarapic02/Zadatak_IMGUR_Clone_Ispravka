<?php

namespace app\cache;

use Predis\Client;
use app\core\Application;
use app\models\Image;
use app\models\Gallery;


class Cache extends Client
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => 'localhost',
            'port'   => 6379,
        ]);
    }

    public function isCached($key1, $key2)
    {
        if(!empty($this->redis->hexists($key1, $key2)))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function clearAll()
    {
        $this->redis->flushall();
    }

    public function clearOne($key)
    {
        $this->redis->del($key);
    }

    public function clearFromHash($key1, $key2)
    {
        $this->redis->hdel($key1, $key2);
    }

    // Images

    public function cacheImages(array $images, $uri, $page)
    {
        $instance = new Image();

        if($instance->checkContentToLoad())
        {
            $key = 'all_photos';
        }
        else
        {
            $key = 'photos';
        }

        for($i = 0; $i < count($images); $i++)
        {
            $arr[$i] = $images[$i];
        }

        if($uri === '/user_profile' || $uri === '/user_photos')
        {
            $userId = $_GET['id'];

            $this->redis->hmset('/user_photos', array('user_' . $userId . '_' . $key . '_page_' . $page => json_encode($arr)));

            $this->redis->expire('/user_photos', 300);
        }
        else if($uri === '/profile')
        {
            $userId = Application::$app->session->getSession('user');

            $this->redis->hmset('/profile', array('user_' . $userId . '_' . $key . '_page_1' => json_encode($arr)));

            $this->redis->expire('/profile', 300);
        }
        else if($uri === '/photos')
        {
            $this->redis->hmset('/photos', array($key . '_page_' . $page => json_encode($arr)));

            $this->redis->expire('/photos', 120);
        }
        else if($uri === '/')
        {
            $this->redis->hmset('/', array($key . '_page_1' => json_encode($arr)));

            $this->redis->expire('/', 120);
        }
    }

    public function getCachedImages($uri, $page)
    {
        $instance = new Image();
        
        if($instance->checkContentToLoad())
        {
            $key = 'all_photos';
        }
        else
        {
            $key = 'photos';
        }

        if($uri === '/user_profile' || $uri === '/user_photos')
        {
            $userId = $_GET['id'];

            $cachedImages = json_decode($this->redis->hget('/user_photos', 'user_' . $userId . '_' . $key . '_page_' . $page), true);
        }
        else if($uri === '/profile')
        {
            $userId = Application::$app->session->getSession('user');

            $cachedImages = json_decode($this->redis->hget('/profile', 'user_' . $userId . '_' . $key . '_page_1'), true);
        }
        else if($uri === '/photos')
        {
            $cachedImages = json_decode($this->redis->hget('/photos', $key . '_page_' . $page), true);
        }
        else if($uri === '/')
        {
            $cachedImages = json_decode($this->redis->hget('/', $key . '_page_1'), true);
        }
    
        for($i = 0; $i < count($cachedImages); $i++)
        {
            $images[$i] = $cachedImages[$i];
        }

        return $images;     
    }

    public function deleteImageFromCache(array $image)
    {
        $userId = $image[0]['user_id'];
        $arr = [];
        array_push($arr, $this->redis->hkeys('/user_photos'));
        array_push($arr, $this->redis->hkeys('/profile'));
        array_push($arr, $this->redis->hkeys('/'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], '_' . $userId . '_') && strpos($keys[$i], 'photos'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/user_photos')))
                {
                    $this->clearFromHash('/user_photos', $keys[$i]);
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $this->clearFromHash('/profile', $keys[$i]);
                }
            }

            if($keys[$i] === 'photos_page_1' || $keys[$i] === 'all_photos_page_1')
            {
                $this->clearFromHash('/', $keys[$i]);
            }
        }
        
        $this->clearOne('/photos');
    }

    public function createImageInCache(array $image)
    {
        $userId = $image[0]['user_id'];
        $arr = [];
        array_push($arr, $this->redis->hkeys('/user_photos'));
        array_push($arr, $this->redis->hkeys('/profile'));
        array_push($arr, $this->redis->hkeys('/'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], '_' . $userId . '_') && strpos($keys[$i], 'photos'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/user_photos')))
                {
                    $this->clearFromHash('/user_photos', $keys[$i]);
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $this->clearFromHash('/profile', $keys[$i]);
                }
            }

            if($keys[$i] === 'photos_page_1' || $keys[$i] === 'all_photos_page_1')
            {
                $this->clearFromHash('/', $keys[$i]);
            }
        }
        
        $this->clearOne('/photos');
    }

    public function editImageFromCache(array $oldImage, array $newImage)
    {
        $oldImage = json_encode($oldImage[0]);
        $newImage = json_encode($newImage[0]);

        $arr = [];
        array_push($arr, $this->redis->hkeys('/'));
        array_push($arr, $this->redis->hkeys('/photos'));
        array_push($arr, $this->redis->hkeys('/user_photos'));
        array_push($arr, $this->redis->hkeys('/profile'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], 'photos'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/')))
                {
                    $value = $this->redis->hget('/', $keys[$i]);
                    $newValue = str_replace($oldImage, $newImage, $value);

                    $this->clearFromHash('/', $keys[$i]);
                    $this->redis->hmset('/', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/photos')))
                {
                    $value = $this->redis->hget('/photos', $keys[$i]);
                    $newValue = str_replace($oldImage, $newImage, $value);

                    $this->clearFromHash('/photos', $keys[$i]);
                    $this->redis->hmset('/photos', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/user_photos')))
                {
                    $value = $this->redis->hget('/user_photos', $keys[$i]);
                    $newValue = str_replace($oldImage, $newImage, $value);

                    $this->clearFromHash('/user_photos', $keys[$i]);
                    $this->redis->hmset('/user_photos', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $value = $this->redis->hget('/profile', $keys[$i]);
                    $newValue = str_replace($oldImage, $newImage, $value);

                    $this->clearFromHash('/profile', $keys[$i]);
                    $this->redis->hmset('/profile', array($keys[$i] => $newValue));
                }
            }
        }
    }

    //End Images

    // Galleries

    public function cacheGalleries(array $galleries, $uri, $page)
    {
        $instance = new Gallery();

        if($instance->checkContentToLoad())
        {
            $key = 'all_galleries';
        }
        else
        {
            $key = 'galleries';
        }

        for($i = 0; $i < count($galleries); $i++)
        {
            $arr[$i] = $galleries[$i];
        }

        if($uri === '/user_profile' || $uri === '/user_galleries')
        {
            $userId = $_GET['id'];

            $this->redis->hmset('/user_galleries', array('user_' . $userId . '_' . $key . '_page_' . $page => json_encode($arr)));

            $this->redis->expire('/user_galleries', 300);
        }
        else if($uri === '/profile')
        {
            $userId = Application::$app->session->getSession('user');

            $this->redis->hmset('/profile', array('user_' . $userId . '_' . $key . '_page_1' => json_encode($arr)));

            $this->redis->expire('/profile', 300);

        }
        else if($uri === '/galleries')
        {
            $this->redis->hmset('/galleries', array($key . '_page_' . $page => json_encode($arr)));

            $this->redis->expire('/galleries', 120);

        }
        else if($uri === '/')
        {
            $this->redis->hmset('/', array($key . '_page_1' => json_encode($arr)));

            $this->redis->expire('/', 120);
        }
    }

    public function getCachedGalleries($uri, $page)
    {
        $instance = new Gallery();
        
        if($instance->checkContentToLoad())
        {
            $key = 'all_galleries';
        }
        else
        {
            $key = 'galleries';
        }

        if($uri === '/user_profile' || $uri === '/user_galleries')
        {
            $userId = $_GET['id'];

            $cachedGalleries = json_decode($this->redis->hget('/user_galleries', 'user_' . $userId . '_' . $key . '_page_' . $page), true);
        }
        else if($uri === '/profile')
        {
            $userId = Application::$app->session->getSession('user');

            $cachedGalleries = json_decode($this->redis->hget('/profile', 'user_' . $userId . '_' . $key . '_page_1'), true);
        }
        else if($uri === '/galleries')
        {
            $cachedGalleries = json_decode($this->redis->hget('/galleries', $key . '_page_' . $page), true);
        }
        else if($uri === '/')
        {
            $cachedGalleries = json_decode($this->redis->hget('/', $key . '_page_1'), true);
        }

        for($i = 0; $i < count($cachedGalleries); $i++)
        {
            $galleries[$i] = $cachedGalleries[$i];
        }

        return $galleries;         
    }

    public function deleteGalleryFromCache(array $gallery)
    {
        $userId = $gallery[0]['user_id'];
        $arr = [];
        array_push($arr, $this->redis->hkeys('/user_galleries'));
        array_push($arr, $this->redis->hkeys('/profile'));
        array_push($arr, $this->redis->hkeys('/'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], '_' . $userId . '_') && strpos($keys[$i], 'galleries'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/user_galleries')))
                {
                    $this->clearFromHash('/user_galleries', $keys[$i]);
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $this->clearFromHash('/profile', $keys[$i]);
                }
            }

            if($keys[$i] === 'galleries_page_1' || $keys[$i] === 'all_galleries_page_1')
            {
                $this->clearFromHash('/', $keys[$i]);
            }
        }
        
        $this->clearOne('/galleries');
    }

    public function createGalleryInCache(array $gallery)
    {
        $userId = $gallery[0]['user_id'];
        $arr = [];
        array_push($arr, $this->redis->hkeys('/user_galleries'));
        array_push($arr, $this->redis->hkeys('/profile'));
        array_push($arr, $this->redis->hkeys('/'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], '_' . $userId . '_') && strpos($keys[$i], 'galleries'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/user_galleries')))
                {
                    $this->clearFromHash('/user_galleries', $keys[$i]);
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $this->clearFromHash('/profile', $keys[$i]);
                }
            }

            if($keys[$i] === 'galleries_page_1' || $keys[$i] === 'all_galleries_page_1')
            {
                $this->clearFromHash('/', $keys[$i]);
            }
        }
        
        $this->clearOne('/galleries');
    }

    public function editGalleryFromCache(array $oldGallery, array $newGallery)
    {
        $oldGallery = json_encode($oldGallery[0]);
        $newGallery = json_encode($newGallery[0]);

        $arr = [];
        array_push($arr, $this->redis->hkeys('/'));
        array_push($arr, $this->redis->hkeys('/galleries'));
        array_push($arr, $this->redis->hkeys('/user_galleries'));
        array_push($arr, $this->redis->hkeys('/profile'));

        $keys = [];

        array_walk_recursive($arr, function($value) use (&$keys){
            $keys[] = $value;
        });

        for($i = 0; $i < count($keys); $i++)
        {
            if(strpos($keys[$i], 'galleries'))
            {
                if(in_array($keys[$i], $this->redis->hkeys('/')))
                {
                    $value = $this->redis->hget('/', $keys[$i]);
                    $newValue = str_replace($oldGallery, $newGallery, $value);

                    $this->clearFromHash('/', $keys[$i]);
                    $this->redis->hmset('/', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/galleries')))
                {
                    $value = $this->redis->hget('/galleries', $keys[$i]);
                    $newValue = str_replace($oldGallery, $newGallery, $value);

                    $this->clearFromHash('/galleries', $keys[$i]);
                    $this->redis->hmset('/galleries', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/user_galleries')))
                {
                    $value = $this->redis->hget('/user_galleries', $keys[$i]);
                    $newValue = str_replace($oldGallery, $newGallery, $value);

                    $this->clearFromHash('/user_galleries', $keys[$i]);
                    $this->redis->hmset('/user_galleries', array($keys[$i] => $newValue));
                }

                if(in_array($keys[$i], $this->redis->hkeys('/profile')))
                {
                    $value = $this->redis->hget('/profile', $keys[$i]);
                    $newValue = str_replace($oldGallery, $newGallery, $value);

                    $this->clearFromHash('/profile', $keys[$i]);
                    $this->redis->hmset('/profile', array($keys[$i] => $newValue));
                }
            }
        }
    }

    //End Galleries

    // User

    public function cacheUser($user, $time)
    {
        if(!Application::$app->isGuest())
        {
            $this->redis->hmset('/profile', array('user' => json_encode($user[0])));

            if(!empty($time))
            {
                $this->redis->expire('/profile' , $time);
            }
        }
    }

    public function getCachedUser()
    {
        $cachedUser = json_decode($this->redis->hget('/profile', 'user'), true);

        $user[0] = $cachedUser;

        return $user;
    }
}