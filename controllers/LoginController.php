<?php

require_once("includes/checkToken.php");

class LoginController extends HTMLController {

    public function loginAction() {
        if (!isset($_GET["token"]) && !isset($_GET["username"])) {
            Header::go("home");
        }

        $token = $_GET["token"];
        $username = $_GET["username"];

        // Don't check whether IPs match if we're on a development environment
        $checkIP = !DEVELOPMENT;
        $info = validate_token($token, $username, array(), $checkIP);

        if (isset($info)) {
            if(session_id() == '') {
                // Session hasn't started
                session_start();
            }

            $_SESSION['username'] = $info['username'];
            $_SESSION['groups'] = $info['groups'];

            $go = "home";

            if (!Player::playerBZIDExists($info['bzid'])) {
                $player = Player::newPlayer($info['bzid'], $info['username']);
                $go = "/profile"; // If they're new, redirect to their profile page so they can add some info
            } else {
                $player = Player::getFromBZID($info['bzid']);
            }

            $_SESSION['playerId'] = $player->getId();
            $player->updateLastLogin();

            Player::saveUsername($player->getId(), $info['username']);
            Visit::enterVisit($player->getId(), $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_REFERER']);

            Header::go($go);

        } else {
            echo "There was an error processing your login. Please go back and try again.";
        }
    }

    public function logoutAction() {
        // destroy the session and redirect to the previous page
        // or to index.php if the page was loaded directly
        $header = new Header();

        session_destroy();

        $loc = "/";
        $override = false;

        if (isset($_SERVER["HTTP_REFERER"])) {
            $loc = $_SERVER["HTTP_REFERER"];
            $override = true;
        }

        Header::go($loc, $override);
    }
}
