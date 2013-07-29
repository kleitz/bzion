<?php

class Header {

    /**
     * The title of the page
     * @var string
     */
    private $title;

    /**
     * The database variable used for queries
     * @var Database
     */
    private $db;

    /**
     * Construct a new Header object
     * @param string $title The page's title
     */
    function __construct($title) {
        $this->title = $title;
        $this->db = Database::getInstance();
    }

    /**
     * Draw the header
     */
    function draw() {

    ?>

<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <title><?php echo $this->title; ?></title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>

        <a href="index.php">Home</a> |

        <?php if (isset($_SESSION['username'])) { ?>

        <a href="profile.php">Profile</a> |
        <a href="logout.php">Logout [<?php echo $_SESSION['username']; ?>]</a>

        <?php } else {
            $url = "http://my.bzflag.org/weblogin.php?action=weblogin&url=";
            $url .= urlencode("http://" . HTTP_ROOT . "/login.php?token=%TOKEN%&username=%USERNAME%");
        ?>
        <a href="<?php echo $url; ?>">Login</a>

        <?php } ?>

    <?php

    }

    /**
     * Redirect the page using PHP's header() function
     * @param string $location The page to redirect to
     */
    function go($location = "index.php") {
        if (strtolower($location) == "default" || strtolower($location) == "index.php") {
            header("Location: index.php");
        } else {
            header("Location: " . $location);
        }
    }
}
