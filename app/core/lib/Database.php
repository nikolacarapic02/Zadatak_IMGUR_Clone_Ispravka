<?php

namespace app\core\lib;

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
        $statement2 = $this->pdo->prepare("ALTER TABLE user ADD COLUMN nsfw tinyint(1) NOT NULL DEFAULT '0'");
        $statement2->execute();
    }

    public function addStatusToUser()
    {
        $statement2 = $this->pdo->prepare("ALTER TABLE user ADD COLUMN status enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active'");
        $statement2->execute();
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
            action longtext COLLATE utf8mb4_unicode_ci NOT NULL
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
        $statement = $this->pdo->prepare("SELECT id, username, email, role, nsfw, status  FROM user WHERE id = $id");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCommentsForImage($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM comment WHERE image_id = $id ORDER BY id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCommentsForGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM comment WHERE gallery_id = $id ORDER BY id DESC");
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

    public function getAllImagesFromGallery($id)
    {
        $statement = $this->pdo->prepare("SELECT image_id FROM image_gallery WHERE gallery_id = '$id' ORDER BY image_id DESC");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getImagesFromGallery($id)
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
        $statement = $this->pdo->prepare("INSERT INTO image (user_id, file_name, slug, nsfw, hidden)
        VALUES ('$user_id', '$file_name', '$slug', 0, 0);");
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