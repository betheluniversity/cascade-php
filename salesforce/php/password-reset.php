<?php

/* TO DO:
    Delete the old entries automatically. Or when reset_password gets called.
    Verify that this does reset the passwords.

*/

////////////////////////////////////////////////////////////////
// Controller
//
////////////////////////////////////////////////////////////////

    $action = $_POST['action'];
    if( $action == "check_credentials"){
        $id = $_POST['id'];
        return check_credentials($id);
    }
    elseif( $action == "reset_password"){
        $id = $_POST['id'];
        $firstPassword = $_POST['firstPassword'];
        $secondPassword = $_POST['secondPassword'];
        echo reset_password($id, $firstPassword, $secondPassword);
    }

////////////////////////////////////////////////////////////////
// Functions
//
////////////////////////////////////////////////////////////////

    function check_credentials($id){
        echo display_password_reset_text_fields();
    }

    function display_password_reset_text_fields(){
        $loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . '/code/salesforce/twig');
        $twig = new Twig_Environment($loader);

//        return '<div><label> Password<input type="password" id="firstPassword" /></label>
//                <br />
//                <label>Retype Password<input type="password" id="secondPassword" /></label>
//                <br />
//                <button id="reset-button">Submit</button></div>';

        //twig version
        //todo test and delete above
        return $twig->render('password-rest.html', array());
    }

    function reset_password($id, $firstPassword, $secondPassword){
        if( $firstPassword != $secondPassword)
           return false;

        // Remove the password resets in the DB that are expired.
        remove_elapsed_times();

        // Create connection
        $con = mysqli_connect("localhost", "root", "", "salesforce");

        if( !$con)
        {
            die('ERROR: Cannot connect to the database.');
        }

        $date = time();
        $query = 'SELECT * FROM forgot_password WHERE `Key`="'.$id.'"';
        $result = mysqli_query($con,$query);

        while ($row=mysqli_fetch_row($result))
        {
            $email = $row[1];
            $date = $row[3];
        }

        // Free result set
        mysqli_free_result($result);

        if( strtotime($date) + 3600 > time())
        {
            define("SOAP_CLIENT_BASEDIR", "toolkit/soapclient");
            require_once (SOAP_CLIENT_BASEDIR.'/SforceEnterpriseClient.php');
            require_once (SOAP_CLIENT_BASEDIR.'/SforceHeaderOptions.php');
            require_once ('userAuth.php');
            $USERNAME = "webmaster@bethel.edu";
            $PASSWORD = "int3rn3tSaqtcCUOBAzIbAwXZDNt2OVc0";
            $mySforceConnection = new SforceEnterpriseClient();
            $mySoapClient = $mySforceConnection->createConnection(SOAP_CLIENT_BASEDIR.'/enterprise.wsdl.xml');
            $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);

            $email = 'jsmith4@gmail.com';
            $search_email = '{' . $email . '}';
            // search for a Contact with this email?
            $response = $mySforceConnection->search("find $search_email in email fields returning contact(id)");
            $records = $response->{'searchRecords'};

            $id = $records[0]->{'Id'};
            $mySforceConnection->setPassword($id, $firstPassword);

            mysqli_close($con);
            return true;
        }


        // 1 hour has elapsed.

        mysqli_close($con);
        return "ERROR: Too much time has elapsed since you requested a new password. Please request a new email and try again.";

    }

    function remove_elapsed_times(){
//        $con = mysqli_connect("localhost", "root", "", "salesforce");
//
//        if( !$con)
//        {
//            die('ERROR: Cannot connect to the database.');
//        }
//
//        $date = time();
        // if date is 1 hour passed, grab it.
//        $query = 'SELECT * FROM forgot_password WHERE `expDate`="'.$id.'"';
//
        // do a foreach over each and remove it from the DB.
//        mysqli_close($con);
    }



?>



