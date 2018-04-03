<!DOCTYPE html>
<html>
    <head>
        <meta charset='UTF-8'>
        <title>Eagle Homes Form Auto-fill</title>
    <h1>Eagle Homes Form Auto-Filler</h1>
    </head>
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
    <script> 
    $(document).ready(function(){
        $('.main').click(function(){
            $($(this).nextAll('.container')[0]).slideToggle('slow');
        });
    });
    </script>
    <style>
        body {
            background-color: #6B86B9;
            color: #000305;
            font-size: 100%;
            font-family: Arial, 'Lucida Sans Unicode';
            line-height: 1.5;
            text-align: left;
            margin: 0 auto;
            padding: 1em;
            width: 1100px;
        }
                
        .field {
            border-radius: 5px;
            background-color: #FFFFFF;
            margin-top: 0.5em;
            margin-bottom: 1.5em;
            border-style: none;
            padding-top: 1.75em;
            padding-left: 2em;
            padding-bottom: 0.75em;
            width: 100%;
            display: table;

        }
        
        .main, .half {
            display: inline-block;
            margin-bottom: 1em;
            float: left;
            width: 100%;
            font-size: 130%;
            font-weight: bold;
            cursor: pointer;
        }
        
        .sub {
            font-size: 110%;
            font-weight: bold;
            float: left;
            padding-left: 3px;
        }
        
        .column {
            width: 50%;
            float: left;
            display: table-cell;
        }
        
        .container {
            display: none;
            width: 100%;
        }
        
        label, input {
            display: inline-block;
            vertical-align: middle;
            padding: 0.2em;
        }
        
        label {
            float: left;
            margin-right: 0.5em;
            text-align: right;
        }
        
        #start > label {
            width: 7em;
        }
        
        #client > label{
            width: 12em;

        }
        
        #forms > label {
            width: 20em;
        }
        
        input {
            width: 200px;
            font-size: 100%;
        }
        
        select {
            display: inline-block;
            vertical-align: middle;
            padding: 0.2em;
            width: 170px;
            float: left;
            font-size: 100%;
        }

        input[type=checkbox] {
            vertical-align: middle;
            position: relative;
            bottom: 1px;
            width: 10px;
        }
        
        button{
            background-color: #FFFFFF;
            border-style: outset;
            border-radius: 3px;
            color: black;
            font-size: 100%;
            padding: 10px 15px;
            text-decoration: none;
        }

        @media only screen and (max-width: 1300px) {
            
            .column {
                width: 100%;
            }
            
            body {
                width: 500px;
            }
        }
        
    </style>
    <body>
        <?php
        session_start();
        $mysqli = mysqli_connect("localhost", "nmurray", "Eagle1234", "eagle_homes");
        if (mysqli_connect_errno()) {
            //echo "Connect failed: %s\n", mysqli_connect_error();
            exit();
        } else {
            //echo"Host information: %s\n", mysqli_get_host_info($mysqli);
        }

        $firstname = filter_input(INPUT_POST, 'firstname');
        $lastname = filter_input(INPUT_POST, 'lastname');
        $email = filter_input(INPUT_POST, 'email');
                
        $sql = "SELECT cell_phone, country_id, city_id, province_id, postal_code FROM contacts WHERE email_address = '" . $email . "'";
        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        
        if (mysqli_num_rows($result) == 1) {
            //if authorized, get the values of f_name l_name
            while ($info = mysqli_fetch_array($result)) {
                $cell_phone = stripslashes($info['cell_phone']);
                $country_id = stripslashes($info['country_id']);
                $province_id = stripslashes($info['province_id']);
                $city_id = stripslashes($info['city_id']);
                $phone = stripslashes($info['cell_phone']);
                $postal_code = stripslashes($info['postal_code']);
            }

            if ($country_id == 39) {
                $country = 'Canada';
            }if($province_id == 52){
                $province = 'British Columbia';
            }
            if($city_id == 21689){
                $city = 'Salmon Arm';
            }

            $info_arr = array(
                'first_name' => $firstname,
                'last_name' => $lastname,
                //'email' => $email,
                'cell_phone' => $cell_phone,
                //'country' => $country,
                //'province' => $province,
                //'city' => $city,
                //'postal_code' => $postal_code         
            );
            
            $pdf_arr = array(
                'site_check_req' => false,
                'single_wide_standard' => false,
                'sales_contract' => false,
                'residential_set_up' => false,
                'purchase_agreement' => true,
                'payment_method' => false,
                'payment_finance' => false,
                'linoleum_waiver' => false,
                'individual_id_record' => false,
                'home_set_up_sheet' => false,
                'gst_new_housing' => false,
                'gst190_calc' => false,
                'double_wide_standard' => false,
                'bill_of_sale' => false,
                'appliance_delivery_waiver' => false,
                'addendum_2' => false,
                'addendum' => false,
            );

            $_SESSION['pdf_arr'] = $pdf_arr;
            $_SESSION['info_arr'] = $info_arr;
        }
            
        $display_block = "          
            <form method='post' action='PHP/genpdf.php' id='mstrform'>
                <div class='field' id='start'>
                    <div class='column'>
                        <header class='half'>Date</header><br><br>
                        <label for='currdate'>Current Date: </label>
                        <input type='date' name='currdate'><br><br>
                    </div>
                    <div class='column'>
                        <header class='half'>Eagle Homes Location</header><br><br>
                        <label for='ealist'>Location: </label>
                        <select name='ealist' form='mstrform'>
                            <option value='salmon arm'>Salmon Arm</option>
                            <option value='kamploops'>Kamloops</option>
                            <option value='castlegar'>Castlegar</option>
                            <option value='cranbrook'>Cranbrook</option>
                        </select><br><br>
                    </div>
                </div>

                <div class='field' id='client'>
                    <header class='main'>Client Information</header><br><br>
                    <div class='column'>                           
                        <label for='lastname'>Purchaser 1 Last Name: </label>
                        <input type='text' id='lastname' name='lastname' value='" . $lastname . "'><br><br>
                        <label for='firstname'>Purchaser 1 First Name: </label>
                        <input type='text' id='firstname' name='firstname' value='" . $firstname . "'><br><br>
                        <label for='lastname2'>Purchaser 2 Last Name: </label>
                        <input type='text' id='lastname2' name='lastname2'><br><br>
                        <label for='firstname2'>Purchaser 2 First Name: </label>
                        <input type='text' id='firstname2' name ='firstname2'><br><br>
                        <label for='addr'>Current Mailing Address: </label>
                        <input type='text' id='addr' name='addr'><br><br>
                        <label for='newaddr'>New Mailing Address: </label>
                        <input type='text' id='newaddr' name='newaddr'><br><br>
                        <label for='nladdr'>New Legal Address: </label>
                        <input type='text' id='nladdr' name='nladdr'><br><br>
                    </div>
                    <div class='column'>
                        <label for='phone'>Telephone: </label>
                        <input type='text' id='phone' name='phone' value='" . $phone . "'><br><br>
                        <label for='altphone'>Alternate Telephone: </label>
                        <input type='text' id='altphone' name='altphone'><br><br>
                        <label for='email'>Email: </label>
                        <input type='text' id='email' name='email' value='" . $email . "'><br><br>
                        <label for='tenants'>Type of Tenants: </label>
                        <select name='tenants' form='mstrform'>
                            <option value='' disabled selected hidden>-</option>
                            <option value='in common'>In Common</option>
                            <option value='kamploops'>Joint</option>
                        </select><br><br>
                        <label for='sale'>Type of Sale: </label>
                        <select name='sale' form='mstrform'>
                            <option value='' disabled selected hidden>-</option>
                            <option value='sale'>Sale</option>
                            <option value='finance'>Finance</option>
                        </select><br><br>
                    </div>
                </div>

                <div class='field' id='purchase'>
                <header class='main'>Purchase Information</header><br><br> 
                    <div class='container'>
                        <div class='column'>
                            <header class='sub'>Purchase</header><br><br>
                            <label for='price'>Price: </label>
                            <input type='text' name='price'><br><br>
                            <label for='gstrebate'>GST Rebate: </label>
                            <select name='gstrebate' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='fridgeprice'>Fridge Price: </label>
                            <input type='text' name='fridgeprice'><br><br>
                            <label for='rngprice'>Range Price: </label>
                            <input type='text' name='rngprice'><br><br>
                            <label for='deposit'>Deposit Payed: </label>
                            <input type='text' name='deposit'><br><br>
                            <label for='depositdue'>Deposit Due: </label>
                            <input type='text' name='depositdue'><br><br><br>
                        </div>

                        <div class='column'>                           
                            <header class='sub'>Trade-In (If Applicable)</header><br><br>
                            <label for='tryear'>Year: </label>
                            <input type='text' name='tryear'><br><br>
                            <label for='trmake'>Make: </label>
                            <input type='text' name='trmake'><br><br>
                            <label for='trmodel'>Model: </label>
                            <input type='text' name='trmodel'><br><br>
                            <label for='trsize'>Size: </label>
                            <input type='text' name='trsize'><br><br>
                            <label for='trcsa'>CSA Label: </label>
                            <select name='trcsa' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='A277'>Residential</option>
                                <option value='Z240'>Manufactured</option>
                            </select><br><br>
                            <label for='trserial'>Serial Number: </label>
                            <input type='text' name='trserial'><br><br>
                            <label for='trmhr'>MHR Number: </label>
                            <input type='text' name='trmhr'><br><br>                            
                        </div>
                    </div>
                </div>

                <div class='field' id='home'>
                    <header class='main'>Home Information</header><br><br> 
                    <div class='container'>
                        <div class='column'>                          
                            <label for='year'>Year: </label>
                            <input type='text' name='tryear'><br><br>
                            <label for='make'>Make: </label>
                            <input type='text' name='make'><br><br>
                            <label for='model'>Model: </label>
                            <input type='text' name='model'><br><br>
                            <label for='size'>Size: </label>
                            <input type='text' name='size'><br><br>
                        </div>
                        <div class='column'>
                            <label for='csa'>CSA Label: </label>
                            <select name='csa' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='A277'>Residential</option>
                                <option value='Z240'>Manufactured</option>
                            </select><br><br>
                            <label for='serial'>Serial Number: </label>
                            <input type='text' name='serial'><br><br>
                            <label for='mhr'>MHR Number: </label>
                            <input type='text' name='mhr'><br><br>
                            <label for='sections'>Sections: </label>
                            <select name='sections' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='double'>Double Section</option>
                                <option value='single'>Single Section</option>
                            </select><br><br>
                        </div>
                    </div>
                </div>

                <div class='field' id='setup'>
                    <header class='main'>Site Setup Information</header><br><br>
                    <div class='container'>
                        <div class='column'>
                            <header class='sub'>Setup</header><br><br>
                            <label for='sitechk'>Site Check: </label>
                            <select name='sitechk' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='drywall'>Drywall Onsite: </label>
                            <select name='drywall' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='fiber'>Fiber Cement: </label>
                            <select name='fiber' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='skirting'>Skirting: </label>
                            <select name='skirting' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                                <option value='fiber'>Fiber Cement</option>
                            </select><br><br>
                            <label for='stairs'>Stairs: </label>
                            <select name='stairs' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                        </div>

                        <div class='column'>
                            <header class='sub'>Roof Info</header><br><br>
                            <label for='regular'>Regular Pitch: </label>
                            <select name='regular' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='std'>Standard</option>
                                <option value='4/12'>4/12</option>
                            </select><br><br>
                            <label for='lrg'>Large Pitch: </label>
                            <select name='lrg' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='5/12'>5/12</option>
                                <option value='6/12'>6/12</option>
                            </select><br><br>
                            <label for='roofonsite'>Roof Pitch Onsite: </label>
                            <select name='roofonsite' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='incl'>Included</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='dormer'>Dormer Onsite: </label>
                            <select name='dormer' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='solar'>Solar Tubes Onsite: </label>
                            <select name='solar' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='skylight'>Skylight Onsite: </label>
                            <select name='skylight' form='mstrform'>
                                <option value='' disabled selected hidden>-</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                        </div>
                    </div>
                </div>
                
                <div class='field' id='delivery'>
                    <header class='main'>Delivery Agreement Information</header><br><br>
                    <div class='container'>
                        <label for='delivery'>Type of Agreement: </label>
                        <select name='delivery' form='mstrform'>
                            <option value='' disabled selected hidden>-</option>
                            <option value='tbd'>To be determined 1</option>
                            <option value='tbd2'>To be determined 2</option>
                        </select><br><br>
                    </div>
                </div>

                <div class='field'  id='forms'>
                    <header class='main'>Applicable Forms</header><br><br>
                    <label>
                        <input type='checkbox' name='sales_contract' value='true'>
                        New Manufactured Home Sales Agreement<br><br>
                    </label>
                </div>
                <button type='submit' value='Generate' id='generate'>Generate Forms</button>
            </form>";
        echo $display_block;
        ?>

    </body>
</html>
