<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 8/5/14
 * Time: 10:13 AM
 */



?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

        <html>

        <head>
            <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

            <title>Password Reset Example</title>

        </head>

        <body>
            <script type="text/javascript" language="javascript">
                $(document).ready(function() {
//                    if (location.protocol != "https:") {
//
//                        document.write("OAuth will not work correctly from plain http. "+
//
//                            "Please use an https URL.");
//
//                    }
//                    else{
                        function check_passwords(){
                            $("#reset-button").click(function(){
                                var firstPassword = document.getElementById("firstPassword").value;
                                var secondPassword = document.getElementById("secondPassword").value;

                                if( firstPassword == secondPassword){
                                    // call reset_password here.
                                    $.ajax({
                                        type: 'POST',
                                        url: 'password-reset.php',
                                        data: {
                                            action : 'reset_password',
                                            id: <?php echo json_encode($_GET['id']); ?>,
                                            firstPassword: firstPassword,
                                            secondPassword: secondPassword
                                        },
                                        success: function(output) {
                                            alert(output);
                                            if( output == true)
                                                window.location.replace("https://staging.bethel.edu/code/salesforce/php/password-reset-confirmation.html");
                                        }
                                    });

                                }
                                else
                                    alert("ERROR. Please make sure the two passwords are identical.");
                            });
                        };

                        $.ajax({
                            type: 'POST',
                            url: 'password-reset.php',
                            data: {
                                action: 'check_credentials',
                                id: <?php echo json_encode($_GET['id']); ?>
                            },
                            success: function(output) {
                                document.getElementById("main-content").innerHTML = output;
                                check_passwords();
                            }
                        });

//                    }
                });
            </script>

            <div id="main-content">test</div>


        </body>

        </html>
