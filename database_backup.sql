-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book_genre`
--

DROP TABLE IF EXISTS `book_genre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_genre` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint unsigned NOT NULL,
  `genre_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_genre_book_id_foreign` (`book_id`),
  KEY `book_genre_genre_id_foreign` (`genre_id`),
  CONSTRAINT `book_genre_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_genre_genre_id_foreign` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_genre`
--

LOCK TABLES `book_genre` WRITE;
/*!40000 ALTER TABLE `book_genre` DISABLE KEYS */;
INSERT INTO `book_genre` VALUES (1,1,1,NULL,NULL),(2,2,2,NULL,NULL),(3,2,4,NULL,NULL),(4,3,3,NULL,NULL),(5,4,2,NULL,NULL),(6,4,4,NULL,NULL),(7,5,1,NULL,NULL),(8,6,6,NULL,NULL),(9,6,7,NULL,NULL),(10,7,3,NULL,NULL),(11,8,4,NULL,NULL),(12,9,1,NULL,NULL),(13,10,2,NULL,NULL),(14,10,7,NULL,NULL),(15,11,2,NULL,NULL),(16,11,6,NULL,NULL),(34,20,1,NULL,NULL),(35,21,4,NULL,NULL),(36,21,5,NULL,NULL),(37,22,2,NULL,NULL),(38,22,4,NULL,NULL),(39,22,5,NULL,NULL),(40,23,1,NULL,NULL),(41,24,2,NULL,NULL),(42,24,4,NULL,NULL),(43,25,2,NULL,NULL),(44,25,4,NULL,NULL),(45,26,4,NULL,NULL),(46,26,5,NULL,NULL),(47,27,1,NULL,NULL),(48,27,4,NULL,NULL),(49,27,5,NULL,NULL),(52,29,1,NULL,NULL),(53,29,4,NULL,NULL),(57,29,5,NULL,NULL),(59,31,4,NULL,NULL),(60,31,5,NULL,NULL),(61,32,1,NULL,NULL),(62,33,2,NULL,NULL),(63,33,4,NULL,NULL);
/*!40000 ALTER TABLE `book_genre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_kana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isbn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `books_user_id_foreign` (`user_id`),
  CONSTRAINT `books_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,5,'吾輩は猫である','わがはいはねこである','夏目漱石','9784101010014','1905-01-01','吾輩は猫であるの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784101010014?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(2,3,'人を動かす','ひとをうごかす','D・カーネギー','9784422100524','1936-11-12','人を動かすの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784422100524?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(3,1,'リーダブルコード','りーだぶるこーど','Dustin Boswell','9784873115658','2012-06-01','リーダブルコードの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784873115658?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(4,5,'7つの習慣','ななつのしゅうかん','スティーブン・R・コヴィー','9784863940246','1989-10-24','7つの習慣の解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784863940246?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(5,2,'坊っちゃん','ぼっちゃん','夏目漱石','9784101010021','1906-04-01','坊っちゃんの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784101010021?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(6,1,'サピエンス全史','さぴえんすぜんし','ユヴァル・ノア・ハラリ','9784309226712','2011-01-01','サピエンス全史の解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784309226712?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(7,4,'Clean Code','くりーんこーど','Robert C. Martin','9784848330598','2008-08-01','Clean Codeの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784848330598?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(8,5,'嫌われる勇気','きらわれるゆうき','岸見一郎・古賀史健','9784776205819','2013-12-13','嫌われる勇気の解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784776205819?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(9,2,'火花','ひばな','又吉直樹','9784163902302','2015-03-11','火花の解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784163902302?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(10,2,'FACTFULNESS','ふぁくとふるねす','ハンス・ロスリング','9784822289607','2018-04-03','FACTFULNESSの解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784822289607?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(11,5,'コンテナ物語','こんてなものがたり','マルク・レヴィンソン','9784822245566','2006-01-01','コンテナ物語の解説文がここに入ります。','https://books.google.com/books/publisher/content/images/frontcover/9784822245566?fife=w400-h600&source=gbs_api','2026-05-20 12:55:32','2026-05-20 12:55:32'),(20,6,'運転者','運転者','喜多川泰','9784799324509','2019-03-30','「...なんで俺ばっかりこんな目に合うんだよ」思わず独り言を言った、そのときだ。ふと目の前に、タクシーが近づいてくるのに気づいた。累計80万部喜多川泰、渾身の感動作!報われない努力なんてない!','https://books.google.com/books/content?id=FpwVxQEACAAJ','2026-05-22 13:42:48','2026-05-22 13:42:48'),(21,6,'自分の中に毒を持て(新装版)','自分の中に毒を持て(新装版)','岡本太郎','9784413096843','2017-12-01','ロングセラーが、満を持しての新創刊。文字が大きく読みやすくなり、カラー口絵付きで、パワーアップして生まれ変わりました!','https://books.google.com/books/content?id=Vl9DswEACAAJ','2026-05-22 14:37:55','2026-05-22 14:37:55'),(22,6,'超訳アドラーの言葉','超訳アドラーの言葉','アルフレッドアドラー','9784799330104','2024-01-26','自らを受け入れ、運命を切り拓け。フロイト、ユングと並ぶ心理学三大巨頭の一人であり、自己啓発の祖。','https://books.google.com/books/content?id=_jKb0AEACAAJ','2026-05-22 15:49:07','2026-05-22 15:49:07'),(23,6,'モンテ=クリスト伯 1','モンテ=クリスト伯 1','アレクサンドル・デュマ','9784334106904','2025-06-11','将来を嘱望された若き船乗りエドモン・ダンテスは、同僚の恨みを買い、無実の罪で投獄され......鮮烈な新訳!(全6巻)','https://books.google.com/books/content?id=4DdD0QEACAAJ','2026-05-22 15:49:40','2026-05-22 15:49:40'),(24,6,'金持ち父さん貧乏父さん','金持ち父さん貧乏父さん','ロバート・キヨサキ','9784480864246','2013-11-01','お金の力を正しく知って、思い通りの人生を手に入れよう。変化の時代のサバイバルツールとして世界中で読まれるベスト&ロングセラー','https://books.google.com/books/content?id=TnqpngEACAAJ','2026-05-22 15:50:36','2026-05-22 15:50:36'),(25,6,'チーズはどこへ消えた?','チーズはどこへ消えた?','スペンサー ジョンソン','9784594030193','2000-11-01','世界のトップ企業が研修テキストに使用する1999年度全米ビジネス書ベストセラー第1位の翻訳。','https://books.google.com/books/content?id=5sXTPQAACAAJ','2026-05-22 15:51:23','2026-05-22 15:51:23'),(26,6,'超訳ブッダの言葉エッセンシャル版','超訳ブッダの言葉エッセンシャル版','ブッダ','9784799318140','2015-11-20',NULL,'https://books.google.com/books/content?id=VDrWjgEACAAJ','2026-05-22 15:52:52','2026-05-22 15:52:52'),(27,6,'超訳歎異抄','超訳歎異抄','安永雄彦','9784799331484','2025-05-01','元銀行員×コンサル×グロービス講師の、異色の僧侶が、 現代のビジネスパーソンにもわかりやすく超訳! ▼「歎異抄」とは? 鎌倉時代後期に書かれた日本の仏教書。 浄土真宗の僧侶である唯円が親鸞の教えを正しく伝えるために著したと言われている。 その内容は人生哲学書としても読まれ、700年以上読み継がれてきた。 西田幾多郎や、司馬遼太郎、遠藤周作、吉本隆明など日本の名だたる思想家・文学者も愛読した名著である。 司馬遼太郎は、無人島に本を1冊持って行くとしたら、『歎異抄』を挙げたほど。','https://books.google.com/books/content?id=dZ9f0QEACAAJ','2026-05-22 15:53:43','2026-05-22 15:53:43'),(29,6,'コンビニ人間','コンビニ人間','村田沙耶香','9784167911300','2018-09-01','コンビニのバイト歴十八年目の古倉恵子。夢の中でもレジを打ち誰よりも大きくお客様に声をかける...現代の実存を軽やかに問う話題作。','https://books.google.com/books/content?id=bz6_uwEACAAJ','2026-05-23 00:04:57','2026-05-23 00:04:57'),(31,6,'新版生き方','新版生き方','稲盛和夫','9784763142894','2026-03-11','累計155万部突破。 名著『生き方』リニューアル。 時代を問わず、年齢も職業も地位も問わず、 読者の心を震わせてきた名著『生き方』。 「誠実に生きよう」 「“人として正しいか”で考えよう」 読む人の生き方に響く名著が、 新サイズで生まれ変わりました。 ・持ち歩きやすい「ポケットサイズ」に ・丈夫で豪華な「ビニール装」。特別造本 京セラ、KDDI創業者が一生涯貫き続けた 人生哲学。 (本書より) 「嘘をついてはいけない、人に迷惑をかけてはいけない、正直であれ、欲張ってはならない、自分のことばかりを考えてはならない――単純な規範を、そのまま経営の指針に据え、守るべき判断基準としたのです。 経営について無知だったこともありますが、一般に広く浸透しているモラルや道徳に反することをして、うまくいくことなど1つもあるはずはないという、単純な確信がありました。','https://books.google.com/books/content?id=pqnd0QEACAAJ','2026-05-25 04:15:18','2026-05-25 04:15:18'),(32,6,'API Test Masterpiece',NULL,'Tech Scholar','9784000000111',NULL,'Successfully registered via Public API.',NULL,'2026-05-29 00:48:04','2026-05-29 00:48:04'),(33,6,'ハーバード、スタンフォード、オックスフォード… 科学的に証明された すごい習慣大百科','ハーバード、スタンフォード、オックスフォード… 科学的に証明された すごい習慣大百科','堀田秀吾','9784815633417','2025-07-02','勉強・ダイエット・貯金・目標達成…は習慣化が10割 仕事、ダイエット、健康管理、勉強、目標達成…すべて成功のカギは「習慣化」にあります。 しかし間違った習慣を身につけてしまったらその代償は大きくなってしまいます。 何をどう習慣化すればいいか、そのために重要になるのが「エビデンス」です。 ・もし「A」をしたら「B」をすると、あらかじめ決めておく ・選択肢は必ず「3つ」用意しておく ・常にポジティブな言葉を使う―つらさに対する耐性が高まる ・52分間作業して、17分休憩する―生産性が高まるetc. 本書は、ハーバード、スタンフォード、オックスフォード…などの研究機関において証明されたテクニックを112個紹介。 見開き図解入りでわかりやすい。気になったテクニックからはじめられ、情報収集のためにも役立ち、また読みものとしても楽しめる一冊です。 ※カバー画像が異なる場合があります。','https://books.google.com/books/content?id=781oEQAAQBAJ','2026-05-29 05:45:41','2026-05-29 05:45:41');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_user_id_book_id_unique` (`user_id`,`book_id`),
  KEY `favorites_book_id_foreign` (`book_id`),
  CONSTRAINT `favorites_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
INSERT INTO `favorites` VALUES (1,1,3,NULL,NULL),(2,1,6,NULL,NULL),(3,1,7,NULL,NULL),(4,2,3,NULL,NULL),(5,2,5,NULL,NULL),(6,2,7,NULL,NULL),(7,2,8,NULL,NULL),(8,3,1,NULL,NULL),(9,3,6,NULL,NULL),(10,3,10,NULL,NULL),(11,4,4,NULL,NULL),(12,4,5,NULL,NULL),(13,4,7,NULL,NULL),(14,5,4,NULL,NULL),(15,5,6,NULL,NULL),(16,5,10,NULL,NULL),(30,6,29,NULL,NULL),(31,7,29,NULL,NULL),(36,6,31,NULL,NULL),(37,6,27,NULL,NULL),(38,6,26,NULL,NULL),(39,6,25,NULL,NULL),(40,6,24,NULL,NULL),(41,6,23,NULL,NULL),(42,6,21,NULL,NULL),(43,6,20,NULL,NULL),(44,6,2,NULL,NULL),(45,6,11,NULL,NULL),(46,6,1,NULL,NULL);
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `genres_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
INSERT INTO `genres` VALUES (1,'小説','2026-05-20 12:55:32','2026-05-25 04:42:34'),(2,'ビジネス','2026-05-20 12:55:32','2026-05-20 12:55:32'),(3,'技術書','2026-05-20 12:55:32','2026-05-20 12:55:32'),(4,'自己啓発','2026-05-20 12:55:32','2026-05-20 12:55:32'),(5,'エッセイ','2026-05-20 12:55:32','2026-05-22 11:17:51'),(6,'歴史','2026-05-20 12:55:32','2026-05-20 12:55:32'),(7,'科学','2026-05-20 12:55:32','2026-05-20 12:55:32'),(8,'芸術','2026-05-20 12:55:32','2026-05-20 12:55:32'),(9,'料理','2026-05-20 12:55:32','2026-05-25 05:09:36'),(10,'旅行','2026-05-20 12:55:32','2026-05-20 12:55:32'),(11,'スピリチュアル','2026-05-20 17:09:37','2026-05-20 17:09:37'),(13,'宇宙工学','2026-05-25 04:41:46','2026-05-25 04:41:46'),(14,'陶芸','2026-05-25 05:07:13','2026-05-25 05:07:13');
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2014_10_12_200000_add_two_factor_columns_to_users_table',1),(4,'2019_08_19_000000_create_failed_jobs_table',1),(5,'2019_12_14_000001_create_personal_access_tokens_table',1),(6,'2026_05_11_055719_create_genres_table',1),(7,'2026_05_11_055908_create_books_table',1),(8,'2026_05_11_060057_create_reviews_table',1),(9,'2026_05_11_060212_create_book_genre_table',1),(10,'2026_05_11_060235_create_favorites_table',1),(11,'2026_05_11_060307_create_review_likes_table',1),(12,'2026_05_20_112345_create_reading_plans_table',1),(13,'2026_05_20_112602_create_notifications_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('06bde19c-aa34-4ccd-ba42-e82de981ddcd','App\\Notifications\\ReminderNotification','App\\Models\\User',1,'{\"plan_id\":16,\"timing\":\"three_days_after\",\"message\":\"The deadline for your plan \'\' has passed.\"}',NULL,'2026-05-22 18:09:27','2026-05-22 18:09:27'),('8d1e2915-09bb-4fc7-8be7-82beb72281ac','App\\Notifications\\ReminderNotification','App\\Models\\User',6,'{\"plan_id\":16,\"timing\":\"three_days_after\",\"message\":\"The deadline for your plan \'\' has passed.\"}','2026-05-22 18:13:16','2026-05-22 18:12:07','2026-05-22 18:13:16'),('9ac09885-7e0d-42e8-baae-31fdd6a0a72b','App\\Notifications\\ReminderNotification','App\\Models\\User',6,'{\"plan_id\":11,\"book_title\":\"\\u81ea\\u5206\\u306e\\u4e2d\\u306b\\u6bd2\\u3092\\u6301\\u3066(\\u65b0\\u88c5\\u7248)\",\"target_date\":\"2026-05-23\",\"timing\":\"three_days_after\",\"message\":\"\\u300c\\u81ea\\u5206\\u306e\\u4e2d\\u306b\\u6bd2\\u3092\\u6301\\u3066(\\u65b0\\u88c5\\u7248)\\u300d\\u306e\\u8aad\\u66f8\\u76ee\\u6a19\\u671f\\u65e5\\uff082026-05-23\\uff09\\u3092\\u7d4c\\u904e\\u3057\\u3066\\u3044\\u307e\\u3059\\u3002\"}','2026-05-25 11:55:09','2026-05-25 03:27:09','2026-05-25 11:55:09'),('d0891468-65e4-4e0d-9576-78dd198e9174','App\\Notifications\\ReminderNotification','App\\Models\\User',6,'{\"plan_id\":1,\"book_title\":\"\\u543e\\u8f29\\u306f\\u732b\\u3067\\u3042\\u308b\",\"target_date\":\"2026-05-23\",\"timing\":\"three_days_after\",\"message\":\"\\u300c\\u543e\\u8f29\\u306f\\u732b\\u3067\\u3042\\u308b\\u300d\\u306e\\u8aad\\u66f8\\u76ee\\u6a19\\u671f\\u65e5\\uff082026-05-23\\uff09\\u3092\\u7d4c\\u904e\\u3057\\u3066\\u3044\\u307e\\u3059\\u3002\"}','2026-05-25 11:55:09','2026-05-25 03:27:09','2026-05-25 11:55:09'),('fe6aa796-4845-44e4-85d0-bb90b61d5328','App\\Notifications\\ReminderNotification','App\\Models\\User',1,'{\"plan_id\":16,\"timing\":\"three_days_after\",\"message\":\"The deadline for your plan \'\' has passed.\"}',NULL,'2026-05-22 17:57:05','2026-05-22 17:57:05');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'test-api-token','cc1e99295388fcbf52f05d3b53e0e95f5fcb00db030f08c888371ef101793b40','[\"*\"]',NULL,NULL,'2026-05-29 00:36:35','2026-05-29 00:36:35'),(2,'App\\Models\\User',6,'api-test-token','53ca2b071496763cf8f6264559e7decae5f3a2991b3484708164513961aa82f9','[\"*\"]','2026-05-29 00:48:04',NULL,'2026-05-29 00:42:11','2026-05-29 00:48:04');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reading_plans`
--

DROP TABLE IF EXISTS `reading_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `target_date` date NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reading_plans_user_id_foreign` (`user_id`),
  KEY `reading_plans_book_id_foreign` (`book_id`),
  CONSTRAINT `reading_plans_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reading_plans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_plans`
--

LOCK TABLES `reading_plans` WRITE;
/*!40000 ALTER TABLE `reading_plans` DISABLE KEYS */;
INSERT INTO `reading_plans` VALUES (1,6,1,'2026-05-23',3,'2026-05-20 12:55:32','2026-05-25 11:54:55'),(11,6,21,'2026-05-23',3,'2026-05-22 15:13:57','2026-05-25 11:55:13'),(12,6,1,'2026-05-25',3,'2026-05-22 17:52:51','2026-05-28 23:47:41'),(16,6,5,'2026-05-21',3,'2026-05-22 17:52:52','2026-05-23 00:16:04'),(17,6,33,'2026-05-30',2,'2026-05-29 05:47:08','2026-05-29 06:06:59'),(18,6,32,'2026-05-30',1,'2026-05-30 00:01:20','2026-05-30 00:01:20');
/*!40000 ALTER TABLE `reading_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_likes`
--

DROP TABLE IF EXISTS `review_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `review_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_likes_user_id_review_id_unique` (`user_id`,`review_id`),
  KEY `review_likes_review_id_foreign` (`review_id`),
  CONSTRAINT `review_likes_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_likes`
--

LOCK TABLES `review_likes` WRITE;
/*!40000 ALTER TABLE `review_likes` DISABLE KEYS */;
INSERT INTO `review_likes` VALUES (1,1,1,NULL,NULL),(2,2,1,NULL,NULL),(3,4,1,NULL,NULL),(4,2,2,NULL,NULL),(5,3,2,NULL,NULL),(6,5,2,NULL,NULL),(7,1,3,NULL,NULL),(8,2,3,NULL,NULL),(9,2,4,NULL,NULL),(10,3,4,NULL,NULL),(11,4,4,NULL,NULL),(12,5,5,NULL,NULL),(13,1,6,NULL,NULL),(14,3,6,NULL,NULL),(15,2,7,NULL,NULL),(16,3,8,NULL,NULL),(17,5,8,NULL,NULL),(18,1,9,NULL,NULL),(19,3,9,NULL,NULL),(20,5,9,NULL,NULL),(21,2,10,NULL,NULL),(22,4,10,NULL,NULL),(23,1,11,NULL,NULL),(24,3,11,NULL,NULL),(25,4,11,NULL,NULL),(26,1,13,NULL,NULL),(27,2,13,NULL,NULL),(28,4,13,NULL,NULL),(29,4,14,NULL,NULL),(30,2,16,NULL,NULL),(31,1,18,NULL,NULL),(32,2,20,NULL,NULL),(33,3,20,NULL,NULL),(34,5,20,NULL,NULL),(35,1,21,NULL,NULL),(36,3,21,NULL,NULL),(37,1,23,NULL,NULL),(38,2,23,NULL,NULL),(39,4,23,NULL,NULL),(40,6,6,NULL,NULL),(41,6,26,NULL,NULL);
/*!40000 ALTER TABLE `review_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `book_id` bigint unsigned NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_user_id_book_id_unique` (`user_id`,`book_id`),
  KEY `reviews_book_id_foreign` (`book_id`),
  CONSTRAINT `reviews_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,3,1,4,'非常に実用的で、明日からの開発にすぐ活かせそうです！','2026-05-20 12:55:32','2026-05-20 12:55:32'),(2,1,1,4,'非常に実用的で、明日からの開発にすぐ活かせそうです！','2026-05-20 12:55:32','2026-05-20 12:55:32'),(3,4,1,5,'文句なしの名著！すべてのアーキテクトに捧げたい一冊。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(4,1,2,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(5,4,2,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(6,2,3,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(7,1,4,3,'標準的な内容で、初心者向けの解説書として読めます。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(8,4,4,5,'文句なしの名著！すべてのアーキテクトに捧げたい一冊。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(9,4,5,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(10,1,5,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(11,2,6,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(12,1,7,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(13,5,7,4,'非常に実用的で、明日からの開発にすぐ活かせそうです！','2026-05-20 12:55:32','2026-05-20 12:55:32'),(14,2,8,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(15,3,8,4,'非常に実用的で、明日からの開発にすぐ活かせそうです！','2026-05-20 12:55:32','2026-05-20 12:55:32'),(16,1,9,2,'期待していましたが、少し物足りない印象です。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(17,3,9,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(18,5,10,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(19,4,10,3,'標準的な内容で、初心者向けの解説書として読めます。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(20,1,10,5,'文句なしの名著！すべてのアーキテクトに捧げたい一冊。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(21,2,11,1,'内容が難しく、途中で挫折してしまいました。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(22,1,11,3,'標準的な内容で、初心者向けの解説書として読めます。','2026-05-20 12:55:32','2026-05-20 12:55:32'),(23,3,11,4,'非常に実用的で、明日からの開発にすぐ活かせそうです！','2026-05-20 12:55:32','2026-05-20 12:55:32'),(25,6,1,5,'何度読んでも面白い！','2026-05-20 17:10:23','2026-05-20 17:10:23'),(26,6,3,5,'何度読んでも面白いです！もう一度読みたいです。','2026-05-22 10:50:52','2026-05-22 10:51:12'),(29,6,20,5,'何度読んでも面白いです！','2026-05-22 14:33:49','2026-05-22 14:33:49'),(32,7,29,5,'面白いです','2026-05-23 00:19:11','2026-05-23 00:19:11'),(35,6,31,5,'とても面白いです。何度も読みたい名著です。','2026-05-25 04:20:07','2026-05-25 04:21:06'),(36,6,11,3,'可もなく不可もなくという感じでした。','2026-05-25 11:51:06','2026-05-25 11:51:06'),(37,6,23,4,'何度読んでも面白いです！','2026-05-28 23:52:46','2026-05-28 23:52:46'),(38,6,21,4,'何度読んでも面白いです！','2026-05-29 00:15:26','2026-05-29 00:15:26');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'山田太郎','yamada@example.com',NULL,'$2y$12$JFgbYhtQddmXcHxPepWEYuh7J62uqdaHoGC9fm2jglWFbioYaJUxi',NULL,NULL,NULL,NULL,'2026-05-20 12:55:31','2026-05-20 12:55:31'),(2,'鈴木花子','suzuki@example.com',NULL,'$2y$12$odNLbpXykBUgowhpEscmyubfBsCUBEgaaehUs9ep7cyMHfhMMCKQa',NULL,NULL,NULL,NULL,'2026-05-20 12:55:31','2026-05-20 12:55:31'),(3,'田中一郎','tanaka@example.com',NULL,'$2y$12$IdlzVsuZqiDfvHNPx5.whuUabcAa0.mz7cM0t7/a5EVnYTR7iKLxi',NULL,NULL,NULL,NULL,'2026-05-20 12:55:31','2026-05-20 12:55:31'),(4,'佐藤美咲','sato@example.com',NULL,'$2y$12$6cww45wJ2qowIbEvx5vcV.m9Jam/grOkjgR2M//xodDrstM8qa7OK',NULL,NULL,NULL,NULL,'2026-05-20 12:55:32','2026-05-20 12:55:32'),(5,'高橋健太','takahashi@example.com',NULL,'$2y$12$1p6nq5sBf2mokqkTl.8/ZuS7Y/QtVNecH60eQqiThk3FxcnSy9pc.',NULL,NULL,NULL,NULL,'2026-05-20 12:55:32','2026-05-20 12:55:32'),(6,'福沢諭吉','yukichi@example.com',NULL,'$2y$12$PV5z2QC9s5Bbbn/S.lr0JOj9KFLkUK3EurElFirpJrWlz9gYwXgh2',NULL,NULL,NULL,NULL,'2026-05-20 12:57:30','2026-05-20 12:57:30'),(7,'山田花子','hanako@example.com',NULL,'$2y$12$5ObHIbcG76/FQPVr4b2Qnu9HCf1lp1SAbPZr.VKdcNc0GzSjrUWae',NULL,NULL,NULL,NULL,'2026-05-23 00:18:24','2026-05-23 00:18:24'),(8,'田中角栄','kakuei@example.com',NULL,'$2y$12$y1LInmnj6ZOJKJAmbdIPpu8WhDvKqBsiYLnoetb72CwZ7oVsIbSf6',NULL,NULL,NULL,NULL,'2026-05-25 00:58:03','2026-05-25 00:58:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-30 13:30:56
