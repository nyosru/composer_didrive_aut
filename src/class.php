<?php

/**
  класс модуля
 */

namespace Didrive;

//if (!defined('IN_NYOS_PROJECT'))
//    throw new \Exception('Сработала защита от розовых хакеров, обратитесь к администрратору');

class AUT {

    public static $vk_app_id = '';
    public static $vk_app_secret = '';

    public static function enterDidrive($db) {

        // если уже вошли
        if (!empty($_SESSION['now_user_di']['id']))
            return false;

        // если есть картинка для входа ... показываем её
        if (file_exists(DR . dir_site_sd . 'didrive_enter_img.jpg'))
            $vv['didrive_enter_img'] = dir_site_sd . 'didrive_enter_img.jpg';

        $vv['id_app'] = self::$vk_app_id; //Айди приложения

        $vv['url_script'] = 'https://' . $_SERVER['HTTP_HOST'] . '/i.didrive.php'; //ссылка на скрипт auth_vk.php
        $vv['vk_api_url'] = '<a href="https://oauth.vk.com/authorize?client_id=' . self::$vk_app_id . '&redirect_uri=' . $vv['url_script'] . '&response_type=code" >Войти через ВК</a></p>';

// после клика по ссылке "войти через вк" отправили запрос и нам пришёл код .. из которого надо достать данные (используем id)

        if (empty($_REQUEST['level']) && !empty($_REQUEST['code'])) {

            $id_app = $vv['id_app']; //Айди приложения
            $secret_app = self::$vk_app_secret; // Защищённый ключ. Можно узнать там же где и айди

            $url_script = $vv['url_script']; //ссылка на этот скрипт

            $t = 'https://oauth.vk.com/access_token?client_id=' . self::$vk_app_id . '&client_secret=' . $secret_app . '&code=' . $_GET['code'] . '&redirect_uri=' . $url_script;
            $token = json_decode(\f\get_curl_https_uri($t), true);

            if (isset($token['error'])) {
                header('Location: /i.didrive.php?warn=произошла ошибка ВК: ' . $token['error']);
            }

            $fields = 'first_name,last_name,photo_200_orig';

            $uinf = json_decode(file_get_contents('https://api.vk.com/method/users.get?uids=' . $token['user_id'] . '&fields=' . $fields . '&access_token=' . $token['access_token'] . '&v=5.80'), true);

            \Nyos\mod\Lk::$type = 'now_user_di';

            if (!empty($uinf['response'][0]['id'])) {

                $_SESSION[\Nyos\mod\Lk::$type] = \Nyos\Mod\Lk::enterVk($db, $uinf['response'][0]['id']);
                // \f\pa($_SESSION[\Nyos\mod\Lk::$type]);

                $folder = \Nyos\Nyos::$folder_now . ( strpos($_SERVER['PHP_SELF'], 'didrive') !== false ? '_di' : '' );

                if ($_SESSION[\Nyos\mod\Lk::$type] === false) {

                    $sql = 'INSERT INTO `gm_user` ( `folder`, `name`, `family`, `avatar`, `soc_web`, `soc_web_link`, `soc_web_id` ) '
                            . ' VALUES ( :folder , :name , :family , :avatar , :soc_web , :soc_web_link , :soc_web_id ); ';
                    $ff = $db->prepare($sql);
                    $ff->execute([
                        ':folder' => $folder,
                        ':name' => $uinf['response'][0]['first_name'],
                        ':family' => $uinf['response'][0]['last_name'],
                        ':avatar' => $uinf['response'][0]['photo_200_orig'],
                        ':soc_web' => 'vk',
                        ':soc_web_link' => 'https://vk.com/id' . $uinf['response'][0]['id'],
                        ':soc_web_id' => $uinf['response'][0]['id']
                    ]);

                    $enter = \Nyos\Mod\Lk::enterVk($db, $uinf['response'][0]['id']);
                    // var_dump($enter);
                    
                }


                // \f\pa($_REQUEST);
                // die();

                \nyos\Msg::sendTelegramm('Вход в управление с ВК' . PHP_EOL
                        . implode(' + ', $uinf['response'][0])
                        , null, 2);

// если это я
                if ($uinf['response'][0]['id'] == '5903492')
                    $_SESSION[\Nyos\mod\Lk::$type]['access'] = 'admin';

                header("Location: /i.didrive.php");
            } else {
                header("Location: /i.didrive.php?warn=что то пошло ен так, повторите и обратитесь в тех. поддержку");
            }
        }


// авторизация через вк
        if (!empty($_REQUEST['uid']) && !empty($_REQUEST['hash'])) {

            // \f\pa($_REQUEST, '', '', 'request');
// проверка хеша при авторизации в вк
            if (1 == 1) {
                $check_hash = false;
// приложуха adommik
                // $ap_id = 7171647;
                $ap_id = self::$vk_app_id;
                // $secret_key = 'srJxX0eTaPnGIEnTcdedCfJ';
                $secret_key = self::$vk_app_secret;
                if ($_REQUEST['hash'] == md5(( $ap_id ?? '' ) . $_REQUEST['uid'] . ( $secret_key ?? '' )))
                    $check_hash = true;
            }

// если хеш норм то проходим авторизацию
            if (isset($check_hash) && $check_hash === true) {

// \f\pa($_REQUEST);
// \f\pa($_SESSION);

                if (!class_exists('Nyos\\mod\\Lk')) {
//throw new \NyosEx('Не обнаружен класс lk');
                    require_once DR . '/vendor/didrive_mod/lk/class.php';
                }

                \Nyos\mod\Lk::$type = 'now_user_di';
                $_SESSION[\Nyos\mod\Lk::$type] = \Nyos\Mod\Lk::enter($db, $_REQUEST['uid']);

// если это я
                if (!empty($_REQUEST['uid']) && $_REQUEST['uid'] == '5903492')
                    $_SESSION[\Nyos\mod\Lk::$type]['access'] = 'admin';

//// если это я
//            if (
//// vk
//                    $_SESSION['now_user_di']['soc_web_id'] == '5903492' || $_SESSION['now_user_di']['uid'] == '5903492'
//// facebook
//                    || $_SESSION['now_user_di']['soc_web_id'] == '10208107614107713'
//            )
//                $_SESSION['now_user_di']['access'] = 'admin';

                $dd = '';

                if (isset($_SESSION[\Nyos\mod\Lk::$type]['new_user_add']) && $_SESSION[\Nyos\mod\Lk::$type]['new_user_add'] === true) {

                    $show_key = ['id', 'avatar'];

                    foreach ($_SESSION[\Nyos\mod\Lk::$type] as $k => $v) {
                        if (in_array($k, $show_key))
                            $dd .= PHP_EOL . $k . ': ' . $v;
                    }

                    \nyos\Msg::sendTelegramm('Вход в управление с ВК (первый вход)' . PHP_EOL
                            . ( $_SESSION['now_user_di']['name'] ?? 'x' ) . ' ' . ( $_SESSION['now_user_di']['family'] ?? 'x' )
                            . $dd
                            , null, 2);
                } else {

                    $show_key = ['id', 'access', 'avatar'];

                    foreach ($_SESSION[\Nyos\mod\Lk::$type] as $k => $v) {
                        if (in_array($k, $show_key))
                            $dd .= PHP_EOL . $k . ': ' . $v;
                    }

                    \nyos\Msg::sendTelegramm('Вход в управление с ВК ' . PHP_EOL
                            . $_SESSION['now_user_di']['name'] . ' ' . $_SESSION['now_user_di']['family']
                            . $dd
                            , null, 2);
                }

                \f\redirect('/', 'i.didrive.php');
                exit;
            }
        }

// проверка в БД (ввели логин пароль)
        if (isset($_POST['login2']) && isset($_POST['pass2'])) {

            echo '<br/>' . __FILE__ . ' ' . __LINE__;
            \Nyos\mod\Lk::$type = 'now_user_di';

            if (!class_exists('Nyos\mod\Lk'))
                require_once DR . '/vendor/didrive_mod/lk/class.php'; // $_SERVER['DOCUMENT_ROOT'] . DS . 'module' . DS . 'lk' . DS . 'class.php';

            try {

                $_SESSION[\Nyos\mod\Lk::$type] = \Nyos\mod\Lk::getUser($db, null, $_POST['login2'], $_POST['pass2'], ( isset($vv['folder']{3}) ? $vv['folder'] . '_di' : null));

                $e = 'По логину: ' . $_POST['login2'];

                \nyos\Msg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e, null, 2);

                if (isset($vv['admin_auerific'])) {
                    foreach ($vv['admin_auerific'] as $k => $v) {
                        \nyos\Msg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e, $v);
                    }
                }

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'Вход произведён'));
            } catch (\Exception $ex) {

                if (strpos($ex->getMessage(), 'no such table: gm_user')) {
// создаём таблицу gm_user
                    \Nyos\mod\Lk::creatTable($db);
                    \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'Таблица данных создана, просим войти повторно'));
                }

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => $ex->getMessage()));
            }
        }

// проверка входа через соц. сервис
        elseif (isset($_POST['token']{1})) {

// \f\pa($_POST);
// require_once $_SERVER['DOCUMENT_ROOT'] . DS . 'module' . DS . 'lk' . DS . 'class.php';


            if (!class_exists('Nyos\\mod\\Lk')) {

//throw new \NyosEx('Не обнаружен класс lk');
                require_once DR . '/vendor/didrive_mod/lk/class.php';
            }

            \Nyos\mod\Lk::$type = 'now_user_di';

            try {

                $_SESSION[\Nyos\mod\Lk::$type] = \Nyos\Mod\Lk::enterSoc($db, ( isset($vv['folder']{0}) ? $vv['folder'] : null), $_POST['token'], 'didrive');

// если это я
                if (
// vk
                        $_SESSION['now_user_di']['soc_web_id'] == '5903492' || $_SESSION['now_user_di']['uid'] == '5903492'
// facebook
                        || $_SESSION['now_user_di']['soc_web_id'] == '10208107614107713'
                )
                    $_SESSION['now_user_di']['access'] = 'admin';

                if (class_exists('\nyos\Msg')) {
                    $e = '';

                    foreach ($_SESSION[\Nyos\mod\Lk::$type] as $k => $v) {
                        if (isset($v{0}))
                            $e .= $k . ': ' . $v . PHP_EOL;
                    }

                    \nyos\Msg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e, null, 1);

// \Nyos\NyosMsg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e,null,1);

                    if (isset($vv['admin_auerific'])) {
                        foreach ($vv['admin_auerific'] as $k => $v) {
                            \nyos\Msg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e, $v);
//\Nyos\NyosMsg::sendTelegramm('Вход в управление ' . PHP_EOL . PHP_EOL . $e, $k );
                        }
                    }
                }

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'Вход произведён'));
            } catch (\NyosEx $ex) {

//            echo '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
//            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
//            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
//            . PHP_EOL . $ex->getTraceAsString()
//            ;
//            die(__LINE__);

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'НЕописуемая ситуация ' . $ex->getMessage()));
            } catch (\Error $ex) {

//            echo '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
//            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
//            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
//            . PHP_EOL . $ex->getTraceAsString()
//            ;
//            die(__LINE__);

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'НЕописуемая ситуация ' . $ex->getMessage()));
            } catch (\Exception $ex) {

//            echo '<pre>--- ' . __FILE__ . ' ' . __LINE__ . '-------'
//            . PHP_EOL . $ex->getMessage() . ' #' . $ex->getCode()
//            . PHP_EOL . $ex->getFile() . ' #' . $ex->getLine()
//            . PHP_EOL . $ex->getTraceAsString()
//            . '</pre>';

                if (strpos($ex->getMessage(), 'no such table: gm_user')) {
// создаём таблицу gm_user
                    \Nyos\mod\Lk::creatTable($db);
                    \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => 'Таблица данных создана, просим войти повторно'));
                }

                \f\redirect('/', 'i.didrive.php', array('rand' => rand(0, 100), 'warn' => $ex->getMessage()));
            }

//die('11111');
//exit;
        }

        return \f\end3('входите удобным способодом', false);

//// $ttwig = $twig->loadTemplate('didrive/tpl/enter.htm');
//        $ttwig = $twig->loadTemplate(\f\like_tpl('enter', $vv['sdd'] . '../tpl/', dir_site_tpldidr, DR));
//        echo $ttwig->render($vv);
//
//// echo '<br/>' . __FILE__ . ' [' . __LINE__ . ']';
//
//        $r = ob_get_contents();
//        ob_end_clean();
//
////        if ($_SERVER['HTTP_HOST'] == 'adomik.uralweb.info') {
////            die('<br/>' . __FILE__ . ' ' . __LINE__);
////        }
//
//        die($r);
//die($r);
    }

}
