<?php

namespace app\core\lib;

use DateTime;

class Database
{
    protected $pdo;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->pdo = new \PDO($config['dsn'], $config['user'], $config['password']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    //Database extensions

    public function addNsfwToUser()
    {
        $statement = $this->pdo->prepare("ALTER TABLE user ADD COLUMN nsfw tinyint(1) NOT NULL DEFAULT '0'");
        $statement->execute();
    }

    public function addStatusToUser()
    {
        $statement = $this->pdo->prepare("ALTER TABLE user ADD COLUMN status enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active'");
        $statement->execute();
    }

    public function dropTableDoctrine()
    {
        $statement = $this->pdo->prepare("DROP TABLE IF EXISTS doctrine_migration_versions;");
        $statement->execute();
    }

    public function createTableModeratorLogging()
    {
        $statement = $this->pdo->prepare("CREATE TABLE moderator_logging(
            moderator_id int(11) NOT NULL,
            image_id int(11) DEFAULT NULL,
            gallery_id int(11) DEFAULT NULL,
            action longtext COLLATE utf8mb4_unicode_ci NOT NULL,
            FOREIGN KEY (moderator_id) REFERENCES user(id),
            FOREIGN KEY (image_id) REFERENCES image(id),
            FOREIGN KEY (gallery_id) REFERENCES gallery(id)
        )");
        $statement->execute();
    }

    public function optimizeDatabase()
    {
        $statement1 = $this->pdo->prepare("CREATE INDEX image_name_slug ON image (file_name, slug)");
        $statement1->execute();

        $statement2 = $this->pdo->prepare("CREATE INDEX gallery_name_slug ON gallery (name, slug)");
        $statement2->execute();

        $statement3 = $this->pdo->prepare("SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'quant-zadatak' ORDER BY TABLE_SCHEMA, TABLE_NAME");
        $statement3->execute();

        if ($statement3->rowCount() > 0) {
            $sql4 = "OPTIMIZE TABLE ";
            $i = 0;
            while ($row = $statement3->fetch(\PDO::FETCH_ASSOC)) {
               $sql4 .= '`' . $row['TABLE_SCHEMA'] . '`.`' . $row['TABLE_NAME'] . '`, ';
               $i++;
            }
            $sql4 = substr($sql4, 0, strlen($sql4) - 2);

            $statement4 = $this->pdo->prepare($sql4);
            $statement4->execute();
        }
    }

    public function createTableSubscription()
    {
        $statement = $this->pdo->prepare("CREATE TABLE subscription(
            id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            user_email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            plan enum('free','1 month', '6 months', '12 months') COLLATE utf8mb4_unicode_ci DEFAULT 'free',
            status enum('active','inactive', 'pending') COLLATE utf8mb4_unicode_ci,
            cancel tinyint(1) NOT NULL DEFAULT '0',
            start_time timestamp,
            expire_time timestamp,
            additional_note longtext COLLATE utf8mb4_unicode_ci NOT NULL,
            FOREIGN KEY (user_id) REFERENCES user(id)
        )");

        $statement->execute();
    }

    public function createTablePayment()
    {
        $statement = $this->pdo->prepare("CREATE TABLE payment(
            id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            subscription_id int(11) NOT NULL,
            amount varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            method enum('credit','paypal', 'crypto') COLLATE utf8mb4_unicode_ci,
            first_name varchar(255) COLLATE utf8mb4_unicode_ci,
            last_name varchar(255) COLLATE utf8mb4_unicode_ci,
            card_type enum('visa', 'mastercard', 'american_express', 'none') COLLATE utf8mb4_unicode_ci,
            card_num varchar(255) COLLATE utf8mb4_unicode_ci,
            paypal_mail varchar(255) COLLATE utf8mb4_unicode_ci,
            crypto_mail varchar(255) COLLATE utf8mb4_unicode_ci,
            data_validity tinyint(1) NOT NULL DEFAULT '1',
            FOREIGN KEY (subscription_id) REFERENCES subscription(id)
        )");

        $statement->execute();
    }

    public function addCreateTimeToImage()
    {
        $statement = $this->pdo->prepare("ALTER TABLE image ADD COLUMN create_time timestamp NOT NULL DEFAULT '2022-01-01 00:00:00'");
        $statement->execute();
    }

    //End Database extensions

    //User

    public function registerUser($attributes)
    {
        $username = $attributes['username'];
        $email = $attributes['email'];
        $password = password_hash($attributes['password'], PASSWORD_DEFAULT);
        $api_key = implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));

        $statement = $this->pdo->prepare("INSERT INTO user(username, email, password, api_key, role, nsfw, status) 
        VALUES ('$username', '$email', '$password', '$api_key', 'user', 0, 'active')");

        $statement->execute();
    }

    public function loginUser($attributes)
    {
        $email = $attributes['email'];

        $statement = $this->pdo->prepare("SELECT * FROM user WHERE email = '$email';");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUser($id)
    {
        $statement = $this->pdo->prepare("SELECT id, username, email, role, nsfw, status FROM user WHERE id = $id");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllUsers()
    {
        $statement = $this->pdo->prepare("SELECT id, username, email, role, nsfw, status FROM user");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createCommentForImage($user_id, $image_id, $comment)
    {
        $statement = $this->pdo->prepare("INSERT INTO comment (user_id , image_id, comment)
        VALUES ($user_id, $image_id, '$comment');");
        $statement->execute();
    }

    public function createCommentForGallery($user_id, $gallery_id, $comment)
    {
        $statement = $this->pdo->prepare("INSERT INTO comment (user_id, gallery_id, comment)
        VALUES ($user_id, $gallery_id, '$comment');");
        $statement->execute();
    }

    public function moderatorImageLogging($user_id, $username, $id, $name, $action)
    {
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $statement = $this->pdo->prepare("INSERT INTO moderator_logging (moderator_id, image_id, action)
        VALUES ($user_id, $id, CONCAT('Moderator ', '$username', ' oznacio sliku ','$name', ' - $url', ' da ', '$action'));");
        $statement->execute();
    }

    public function moderatorGalleryLogging($user_id, $username, $id, $name, $action)
    {
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $statement = $this->pdo->prepare("INSERT INTO moderator_logging (moderator_id, gallery_id, action)
        VALUES ($user_id, $id, CONCAT('Moderator ', '$username', ' oznacio galeriju ','$name', ' - $url', ' da ', '$action'));");
        $statement->execute();
    }

    public function changeUserStatus($id, $status)
    {
        $statement = $this->pdo->prepare("UPDATE user SET status = '$status' WHERE id = '$id'");
        $statement->execute();
    }

    public function changeUserRole($id, $role)
    {
        $statement = $this->pdo->prepare("UPDATE user SET role = '$role' WHERE id = '$id'");
        $statement->execute();
    }

    public function getModeratorLogging()
    {
        $statement = $this->pdo->prepare("SELECT * FROM moderator_logging");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    //Subscriptions

    public function subscribeToPlan($user_id, $attributes)
    {
        $email = $attributes['email'];
        $plan = $attributes['plan'];
        $note = $attributes['note'];
        $start_time = date('Y-m-d H:i:s');
        $date = new DateTime('now');

        if($plan === '1 month')
        {
            $date->modify('+1 month');
            $expire = $date->format('Y-m-d H:i:s');
        }
        else if($plan === '6 months')
        {
            $date->modify('+6 month');
            $expire = $date->format('Y-m-d H:i:s');
        }
        else
        {
            $date->modify('+12 month');
            $expire = $date->format('Y-m-d H:i:s');
        }

        $statement1 = $this->pdo->prepare("INSERT INTO subscription(user_id, user_email, plan, start_time, expire_time, status, additional_note) 
        VALUES ('$user_id', '$email', '$plan', '$start_time', '$expire', 'active', '$note')");
        $statement1->execute();

        $statement2 = $this->pdo->prepare("SELECT LAST_INSERT_ID() as 'id'");
        $statement2->execute();

        $value = $statement2->fetchAll(\PDO::FETCH_ASSOC);

        $subscription_id = $value[0]['id'];
        $amount = $attributes['amount'];
        $method = $attributes['method'];
        $first_name = $attributes['first_name'];
        $last_name = $attributes['last_name'];
        $card_num = $attributes['card_num'];
        $paypal_email = $attributes['paypal_mail'];
        $crypto_email = $attributes['crypto_mail'];

        if($method === 'credit')
        {
            if($attributes['card_type'] === 1)
            {
                $card_type = 'visa';
            }
            else if($attributes['card_type'] === 2)
            {
                $card_type = 'mastercard';
            }
            else
            {
                $card_type = 'american_express';
            }
        }
        else
        {
            $card_type = 'none';
        }

        $statement3 = $this->pdo->prepare("INSERT INTO payment (subscription_id, amount, method, first_name, last_name, card_type, card_num, paypal_mail, crypto_mail) 
        VALUES ('$subscription_id', '$amount', '$method', '$first_name', '$last_name', '$card_type', '$card_num', '$paypal_email', '$crypto_email')");
        $statement3->execute();
    }

    public function renewalPlan($user_id, $planInfo, $paymentInfo)
    {
        $email = $planInfo['user_email'];
        $plan = $planInfo['plan'];
        $note = $planInfo['additional_note'];
        $start_time = date('Y-m-d H:i:s');
        $date = new DateTime('now');

        if($plan === '1 month')
        {
            $date->modify('+1 month');
            $expire = $date->format('Y-m-d H:i:s');
        }
        else if($plan === '6 months')
        {
            $date->modify('+6 month');
            $expire = $date->format('Y-m-d H:i:s');
        }
        else
        {
            $date->modify('+12 month');
            $expire = $date->format('Y-m-d H:i:s');
        }

        $statement1 = $this->pdo->prepare("INSERT INTO subscription(user_id, user_email, plan, start_time, expire_time, status, additional_note) 
        VALUES ('$user_id', '$email', '$plan', '$start_time', '$expire', 'active', '$note')");
        $statement1->execute();

        $statement2 = $this->pdo->prepare("SELECT LAST_INSERT_ID() as 'id'");
        $statement2->execute();
        
        $value = $statement2->fetchAll(\PDO::FETCH_ASSOC);

        $subscription_id = $value[0]['id'];
        $amount = $paymentInfo['amount'];
        $method = $paymentInfo['method'];
        $first_name = $paymentInfo['first_name'];
        $last_name = $paymentInfo['last_name'];
        $card_num = $paymentInfo['card_num'];
        $paypal_email = $paymentInfo['paypal_mail'];
        $crypto_email = $paymentInfo['crypto_mail'];

        if($method === 'credit')
        {
            if($paymentInfo['card_type'] === 1)
            {
                $card_type = 'visa';
            }
            else if($paymentInfo['card_type'] === 2)
            {
                $card_type = 'mastercard';
            }
            else
            {
                $card_type = 'american_express';
            }
        }
        else
        {
            $card_type = 'none';
        }

        $statement3 = $this->pdo->prepare("INSERT INTO payment (subscription_id, amount, method, first_name, last_name, card_type, card_num, paypal_mail, crypto_mail) 
        VALUES ('$subscription_id', '$amount', '$method', '$first_name', '$last_name', '$card_type', '$card_num', '$paypal_email', '$crypto_email')");
        $statement3->execute();
    }

    public function upgradeExistingPlan($user_id, $attributes)
    {
        $email = $attributes['email'];
        $plan = $attributes['plan'];
        $note = $attributes['note'];
        $amount = $attributes['amount'];
        $method = $attributes['method'];
        $first_name = $attributes['first_name'];
        $last_name = $attributes['last_name'];
        $card_num = $attributes['card_num'];
        $paypal_email = $attributes['paypal_mail'];
        $crypto_email = $attributes['crypto_mail'];
        
        $oldPlan = $this->getPlanInfo($user_id);
        $oldPayment = $this->getPaymentInfo($oldPlan[0]['id']);

        if(trim($amount, '$') > trim($oldPayment[0]['amount'], '$'))
        {
            $start_time = date('Y-m-d H:i:s');
            $date = new DateTime('now');

            if($plan === '6 months')
            {
                $date->modify('+6 month');
                $expire = $date->format('Y-m-d H:i:s');
            }
            else
            {
                $date->modify('+12 month');
                $expire = $date->format('Y-m-d H:i:s');
            }

            $this->setPlanStatus($oldPlan[0]['id'], 'inactive');

            $statement1 = $this->pdo->prepare("INSERT INTO subscription(user_id, user_email, plan, start_time, expire_time, status, additional_note) 
            VALUES ('$user_id', '$email', '$plan', '$start_time', '$expire', 'active', '$note')");
            $statement1->execute();
        }
        else
        {
            $start_time = $oldPlan[0]['expire_time'];

            if($plan === '1 month')
            {
                $expire = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($start_time)));
            }
            else
            {
                $expire = date('Y-m-d H:i:s', strtotime('+6 month', strtotime($start_time)));
            }

            $statement1 = $this->pdo->prepare("INSERT INTO subscription(user_id, user_email, plan, start_time, expire_time, status, additional_note) 
            VALUES ('$user_id', '$email', '$plan', '$start_time', '$expire', 'pending', '$note')");
            $statement1->execute();
        }

        $statement2 = $this->pdo->prepare("SELECT LAST_INSERT_ID() as 'id'");
        $statement2->execute();

        $value = $statement2->fetchAll(\PDO::FETCH_ASSOC);

        $subscription_id = $value[0]['id'];

        if($method === 'credit')
        {
            if($attributes['card_type'] === 1)
            {
                $card_type = 'visa';
            }
            else if($attributes['card_type'] === 2)
            {
                $card_type = 'mastercard';
            }
            else
            {
                $card_type = 'american_express';
            }
        }
        else
        {
            $card_type = 'none';
        }

        $statement3 = $this->pdo->prepare("INSERT INTO payment (subscription_id, amount, method, first_name, last_name, card_type, card_num, paypal_mail, crypto_mail) 
        VALUES ('$subscription_id', '$amount', '$method', '$first_name', '$last_name', '$card_type', '$card_num', '$paypal_email', '$crypto_email')");
        $statement3->execute();
    }

    public function cancelSubscriptionForUser($user_id)
    {
        $statement = $this->pdo->prepare("UPDATE subscription SET cancel = 1 WHERE user_id = '$user_id' AND status = 'active'");
        $statement->execute();
    }

    public function setPlanStatus($plan_id, $status)
    {
        $statement = $this->pdo->prepare("UPDATE subscription SET status = '$status' WHERE id = '$plan_id' AND (status = 'active' OR status = 'pending')");
        $statement->execute();
    }

    public function getPlanInfo($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM subscription WHERE user_id = $id and status = 'active'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPendingPlanInfo($user_id)
    {
        $statement = $this->pdo->prepare("SELECT id, plan, start_time, expire_time, status, cancel FROM subscription WHERE user_id = $user_id and status = 'pending'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllPlansInfo($id)
    {
        $statement = $this->pdo->prepare("SELECT id, plan, start_time, expire_time, status, cancel FROM subscription WHERE user_id = $id");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function checkLastExpiredPlan($user_id)
    {
        $statement = $this->pdo->prepare("SELECT MAX(expire_time) as 'expire_time' FROM subscription WHERE user_id = $user_id AND status = 'inactive'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function changePayPalDataValidity($email)
    {
        $statement = $this->pdo->prepare("UPDATE payment SET data_validity = 0 WHERE paypal_mail = '$email'");
        $statement->execute();
    }

    public function changeCryptoDataValidity($email)
    {
        $statement = $this->pdo->prepare("UPDATE payment SET data_validity = 0 WHERE crypto_mail = '$email'");
        $statement->execute();
    }

    public function changeCreditCardDataValidity()
    {
        $statement = $this->pdo->prepare("UPDATE payment SET data_validity = 0 WHERE ");
        $statement->execute();
    }

    public function getPaymentInfo($subscription_id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM payment WHERE subscription_id = '$subscription_id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function checkDataValidityForPayment($subscription_id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM payment WHERE subscription_id = '$subscription_id' AND 'data_validity' = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    //End Subscriptions

    //End User

    //Image

    public function getImagesForPage($page)
    {
        $limit = 16;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM image WHERE nsfw = 0 AND hidden = 0 ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllImagesForPage($page)
    {
        $limit = 16;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM image ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSingleImageById($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM image WHERE id = '$id' AND nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSingleImageByIdWithoutRule($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM image WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSingleImageBySlugWithoutRule($slug)
    {
        $statement = $this->pdo->prepare("SELECT * FROM image WHERE slug = '$slug'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllImagesKeysFromGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT image_id FROM image_gallery WHERE gallery_id = '$id' ORDER BY image_id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getImagesKeysFromGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT ig.image_id, ig.gallery_id
        FROM image_gallery ig
        WHERE ig.gallery_id = $id AND ig.image_id IN(SELECT id FROM image WHERE nsfw = 0 AND hidden = 0) ORDER BY ig.image_id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfImages()
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM image WHERE nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfAllImages()
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM image");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfYourImages($user_id)
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM image WHERE user_id = '$user_id' AND nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfYourAllImages($user_id)
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM image WHERE user_id = '$user_id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCommentsForImage($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM comment WHERE image_id = $id ORDER BY id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editImageByModerator($nsfw, $hidden, $id)
    {
        $statement = $this->pdo->prepare("UPDATE image SET nsfw = '$nsfw', hidden = '$hidden' WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editImageByAdmin($file_name, $newSlug, $nsfw, $hidden, $id)
    {
        $statement = $this->pdo->prepare("UPDATE image SET file_name = '$file_name', slug = '$newSlug', nsfw = '$nsfw', hidden = '$hidden' WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getImagesForUser($user_id, $page)
    {
        $limit = 8;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM image WHERE user_id = $user_id AND nsfw = 0 AND hidden = 0 ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllImagesForUser($user_id, $page)
    {
        $limit = 8;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM image WHERE user_id = $user_id ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createImage($file_name, $slug, $user_id)
    {
        $time = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare("INSERT INTO image (user_id, file_name, slug, nsfw, hidden, create_time)
        VALUES ('$user_id', '$file_name', '$slug', 0, 0, '$time');");
        $statement->execute();

        $statement1 = $this->pdo->prepare("SELECT LAST_INSERT_ID() as 'id';");
        $statement1->execute();

        return $statement1->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function AddToTableImageGallery($image_id, $gallery_id)
    {
        $statement = $this->pdo->prepare("INSERT INTO image_gallery (image_id, gallery_id)
            VALUES ($image_id, $gallery_id)");
        $statement->execute();
    }

    public function editImage($name, $slug, $id, $user_id)
    {
        $statement = $this->pdo->prepare("UPDATE image SET file_name = '$name', slug = '$slug' WHERE id = '$id' AND user_id = '$user_id';");
        $statement->execute();
    }

    public function deleteImageGalleryKey($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM image_gallery WHERE image_id = '$id'");
        $statement->execute();
    }

    public function deleteImageCommentKey($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM comment WHERE image_id = '$id'");
        $statement->execute();
    }

    public function deleteImage($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM image WHERE id = '$id'");
        $statement->execute();
    }

    public function getCountOfCreatedImages($user_id, $first_date, $last_date)
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) as 'num' FROM image WHERE user_id = $user_id AND create_time BETWEEN '$first_date' AND '$last_date'");
        $statement->execute();
        
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    //End Image

    //Gallery

    public function getGalleriesForPage($page)
    {
        $limit = 16;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE nsfw = 0 AND hidden = 0 ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllGaleriesForPage($page)
    {
        $limit = 16;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM gallery ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSingleGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE id = '$id' AND nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSingleGalleryWithoutRule($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getGalleryByName($gallery_name)
    {
        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE name = '$gallery_name';");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getYourGalleryByName($gallery_name, $user_id)
    {
        $statement = $this->pdo->prepare("SELECT id FROM gallery WHERE name = '$gallery_name' AND user_id = '$user_id';");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfGalleries()
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM gallery WHERE nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfAllGalleries()
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM gallery");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfYourGalleries($user_id)
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM gallery WHERE user_id = '$user_id' AND nsfw = 0 AND hidden = 0");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getNumOfYourAllGalleries($user_id)
    {
        $statement = $this->pdo->prepare("SELECT COUNT(id) as 'num' FROM gallery WHERE user_id = '$user_id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCommentsForGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM comment WHERE gallery_id = $id ORDER BY id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editGalleryByModerator($nsfw, $hidden, $id)
    {
        $statement = $this->pdo->prepare("UPDATE gallery SET nsfw = '$nsfw', hidden = '$hidden' WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $id)
    {
        $statement = $this->pdo->prepare("UPDATE gallery SET name = '$name', description = '$description', slug = '$slug', nsfw = '$nsfw', hidden = '$hidden' WHERE id = '$id'");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getGalleriesForUser($user_id, $page)
    {
        $limit = 8;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE user_id = $user_id AND nsfw = 0 AND hidden = 0 ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllGalleriesForUser($user_id, $page)
    {
        $limit = 8;
        if(empty($page))
        {
            $page = 1;
        }
        $start = ($page-1) * $limit;

        $statement = $this->pdo->prepare("SELECT * FROM gallery WHERE user_id = $user_id ORDER BY id DESC LIMIT $start, $limit");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function createGallery($name, $slug, $description, $user_id)
    {
        $statement = $this->pdo->prepare("INSERT INTO gallery (user_id, name, description, slug, nsfw, hidden)
        VALUES ('$user_id', '$name', '$description', '$slug', 0, 0);");
        $statement->execute();

        $statement1 = $this->pdo->prepare("SELECT LAST_INSERT_ID() as 'id';");
        $statement1->execute();

        return $statement1->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function editGallery($name, $slug, $description, $id, $user_id)
    {
        $statement = $this->pdo->prepare("UPDATE gallery SET name = '$name', slug = '$slug', description = '$description' WHERE id = '$id' AND user_id = '$user_id';");
        $statement->execute();
    }

    public function deleteGalleryImageKey($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM image_gallery WHERE gallery_id = '$id'");
        $statement->execute();
    }

    public function deleteGalleryCommentKey($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM comment WHERE gallery_id = '$id'");
        $statement->execute();
    }

    public function deleteGallery($id)
    {
        $statement = $this->pdo->prepare("DELETE FROM gallery WHERE id = '$id'");
        $statement->execute();
    }


    //End Gallery
}