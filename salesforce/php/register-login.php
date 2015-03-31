<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/29/14
 * Time: 4:55 PM
 */

session_start();
$username = $_SESSION['username'];
$password = $_SESSION['password'];

$loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/salesforce/twig');
$twig = new Twig_Environment($loader);


?>
<form method="post" style="display:none" id="register-login" action="https://auth.xp.bethel.edu/cas/login?service=https://auth.xp.bethel.edu/auth/sf-portal-login.cgi">

    <?php
//        echo "<input type='text' name='username' id='username' value='$username'/>";
//        echo "<input type='password' name='password' id='password' value='$password'/>";

        //twig version
        //todo test and delete above
        echo $twig->render('faculty.html', array(
            'username' =>$username,
            'password' => $password));
    ?>
</form>



<script type="text/javascript">
    document.getElementById('register-login').submit();
</script>