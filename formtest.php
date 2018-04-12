<!DOCTYPE html>
<html>
    <head>
        <meta charset='UTF-8'>
        <title>Eagle Homes Form Auto-filler</title>
        <style>
            body {
                background-color: #73B8E5;
                color: #303030;
                font-size: 100%;
                font-family: Arial, 'Lucida Sans Unicode';
                line-height: 1.5;
                text-align: left;
                margin: auto;
                padding: 1em;
                width: 850px;
            }

            form {
                width: 100%;
            }

            h1 {
                color: #FFFFFF;
                display: block;
                float: right;
                padding: 0em 12em 0em 0em;
                display: table-cell;
                vertical-align: middle;
            }

            #wrap {
                width: 107.6%;
                height: 125px;
                display: inline-block;
                vertical-align: middle;
            }

            #head {    
                width: 100%;
                height: 78px;
                background-color: #00478e;
                border-radius: 2px;   
                position: relative;
                margin: 1em 0em 0em 0em;
                z-index: -10;
                display: table;
            }

            #eagle {
                max-width: 250px;
                margin-left: 2em;
                display: inline-block;
                vertical-align: middle;
                position: absolute;
                box-shadow: 5px 5px 8px #3d3d3d;
                border-radius: 2px;
            }

            .field {
                border-radius: 2px;
                background-color: #FFFFFF;
                margin-top: 1em;
                margin-bottom: 1.5em;
                border-style: none;
                box-shadow: 5px 5px 8px #3d3d3d;
                padding: 2.5em 2em 1em 2em;
                width: 100%;
                display: table;
                alignment-adjust: middle;
            }

            .main, .half {
                display: inline-block;
                margin: 0em 0em 1.3em 0.2em;
                float: left;
                width: 100%;
                height: 100%;
                font-size: 140%;
                font-weight: bold;
                cursor: pointer;
            }

            .sub {
                font-size: 110%;
                font-weight: bold;
                float: left;
                margin-left: 8em; 
            }

            #arrow {
                max-width: 20px;
                display: inline-block;
                margin-left: 0.5em;
                padding-bottom: 2px;
            }

            .flip {
                transform: rotate(-180deg);
                padding-top: 2px;
            }

            .column {
                width: 50%;
                float: left;
                display: block;
            }

            #forms > div.column {
                padding-left: 1em;
                width: 48%;
                float: left;
                display: inline-block;
            }

            .wide {
                float: right;
                display: block;
                width: 100%;
            }

            .container {
                display: none;
                width: 100%;
            }

            .wide > label + input {
                width: 70%;
                margin: 0 7.2% 0 2%;
            }

            .wide > label {
                width: 20%;    
            }

            label, input, select {
                display: inline-block;
            }

            label {
                width: 40%;
                text-align: right;
            }

            label + input {
                width: 40%;
                margin: 0 15% 0 2%;
            }

            label + select {
                width: 30%;
                margin: 0 26.1% 0 2%;
            }

            input, select {
                font-size: 95%;
                float: right;
                border-radius: 2px;
                border-color: #777777; 
                border-style: solid;
                border-width: 1px;
                padding: 2px;
            }

            select {
                height: 25px;
            }

            input[type=checkbox] {
                width: 15px;
                margin: 5px 10px 0px 10px;
                float: left;
            }

            input[type=date] {
                font-size: 95%;
                font-family: Arial, 'Lucida Sans Unicode';
                padding: 2px;
            }

            button {
                background-color: #00478e;
                color: #FFFFFF;
                border-style: none;
                border-radius: 2px;
                font-size: 100%;
                padding: 10px 15px;
                margin: 0.3em -3.2em 1.5em 0em;
                box-shadow: 5px 5px 8px #3d3d3d;
                text-decoration: none;
                display: block;
                float: right;
            }

        @media only screen and (max-width: 1350px) {
            
            .column {
                width: 100%;
            }
            
            #forms > div.column {
                width: 100%;
            }
            
            body {
                width: 500px;
            }
            
            .wide > label + input {
                width: 60%;
                margin: 0 4% 0 2%;
            }
            
            .wide > label {
                width: 30%;    
            }
            
            #head {    
                width: 105%;
            }
            
            h1 {
                float: left;
                padding: 0em 0em 0em 1em;
            }
            
            #eagle {
                display: none;
            }     
        }
    </style>
    <div id="wrap"><img src="images/eh_logo.PNG" id="eagle"><div id="head"><h1>Form Auto-Filler</h1></div></div>
    </head>
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
    <script> 
    $(document).ready(function() {
        $('.main').click(function(){
            $($(this).nextAll('.container')[0]).slideToggle('slow');
            $(this).children('.arrow').toggleClass('flip'); 
        });
    });
    </script>
    <script>
    function clicked(e) {
        if(!confirm('Are you sure?'))e.preventDefault();
    }
    </script>
    
    <body>
        <?php
//        $mysqli = mysqli_connect("localhost", "nmurray", "Eagle1234", "eagle_homes");
//        if (mysqli_connect_errno()) {
//            //echo "Connect failed: %s\n", mysqli_connect_error();
//            exit();
//        } else {
//            //echo"Host information: %s\n", mysqli_get_host_info($mysqli);
//        }
//        $email_in = filter_input(INPUT_POST, 'email');        
//        $sql = "SELECT first_name, last_name, email_address, home_phone, cell_phone, postal_code, address1, partner_first_name, partner_last_name FROM contacts WHERE email_address = '" . $email_in . "'";
//        $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
        
//        if (mysqli_num_rows($result) == 1) {
//            //if authorized, create variables from results
//            while ($info = mysqli_fetch_array($result)) {
//                $firstname = stripslashes($info['first_name']);
//                $lastname = stripslashes($info['last_name']);
//                $email = stripslashes($info['email_address']);
//                $phone = stripslashes($info['home_phone']);
//                $altphone = stripslashes($info['cell_phone']);
//                $address = stripslashes($info['address1']) + ' ' + stripslashes($info['postal_code']);
//                $firstname2 = stripslashes($info['partner_first_name']);
//                $lastname2 = stripslashes($info['partner_last_name']);
//            }
//        }
        
        /* Code for variables to go into forms
         * 
         * value='" .$firstname. "'
         * value='" .$lastname. "'
         * value='" .$email. "'
         * value='" .$phone. "'
         * value='" .$altphone. "'
         * value='" .$address. "'
         * value='" .$firstname2. "'
         * value='" .$lastname2. "' */
            
        $display_block = "        
            <form method='POST' action='PHP/genpdf.php' id='mstrform'>
                <div class='field' id='start'>
                    <div class='column'>
                        <label for='currdate'>Current Date: </label>
                        <input type='date' name='currdate'><br><br>
                    </div>
                    <div class='column'>
                        <label for='ealist'>Location: </label>
                        <select name='ealist' form='mstrform'>
                            <option value='salmon arm'>Salmon Arm</option>
                            <option value='kamloops'>Kamloops</option>
                            <option value='castlegar'>Castlegar</option>
                            <option value='cranbrook'>Cranbrook</option>
                        </select><br><br>
                    </div>
                </div>

                <div class='field' id='client'>
                    <header class='main'>Client Information</header><br><br>
                    <div class='column'>                           
                        <label for='lastname'>Last Name: </label>
                        <input type='text' id='lastname' name='lastname'><br><br>
                        <label for='firstname'>First Name: </label>
                        <input type='text' id='firstname' name='firstname'><br><br>
                        <label for='lastname2'>Last Name(2): </label>
                        <input type='text' id='lastname2' name='lastname2'><br><br>
                        <label for='firstname2'>First Name(2): </label>
                        <input type='text' id='firstname2' name ='firstname2'><br><br>                       
                    </div>
                    <div class='column'>
                        <label for='email'>Email: </label>
                        <input type='email' id='email' name='email' placeholder='example@website.com'><br><br>
                        <label for='phone'>Telephone: </label>
                        <input type='text' id='phone' name='phone' placeholder='555-555-5555'><br><br>
                        <label for='altphone'>Alternate Telephone: </label>
                        <input type='text' id='altphone' name='altphone' placeholder='555-555-5555'><br><br>
                        <label for='tenants'>Type of Tenants: </label>
                        <select name='tenants' form='mstrform'>
                            <option value='' selected>-</option>
                            <option value='incommon'>In Common</option>
                            <option value='joint'>Joint</option>
                        </select><br><br>
                        <label for='sale'>Type of Sale: </label>
                        <select name='sale' form='mstrform'>
                            <option value='' selected>-</option>
                            <option value='sale'>Sale</option>
                            <option value='finance'>Finance</option>
                        </select><br><br>
                    </div>
                    <div class='wide'>
                        <label for='addr'>Mailing Address: </label>
                        <input type='text' id='addr' name='addr' placeholder='Street Address, City, Province and Postal Code'><br><br>
                        <label for='newaddr'>New Address: </label>
                        <input type='text' id='newaddr' name='newaddr' placeholder='Street Address, City, Province and Postal Code'><br><br>
                        <label for='nladdr'>New Legal Address: </label>
                        <input type='text' id='nladdr' name='nladdr' placeholder='Street Address, City, Province and Postal Code'><br><br>
                    </div>
                </div>

                <div class='field' id='purchase'>
                <header class='main'>Purchase Information<img id='arrow' class='arrow' src='images/triangle-arrow.png' alt=''></header><br><br> 
                    <div class='container'>
                        <div class='column'>
                            <header class='sub'>Purchase Info</header><br><br>
                            <label for='Home_Price'>Home Price: $</label>
                            <input type='text' name='Home_Price' placeholder='0.00'><br><br>                           
                            <label for='fridgeprice'>Fridge Price: $</label>
                            <input type='text' name='fridgeprice' placeholder='0.00'><br><br>
                            <label for='rngprice'>Range Price: $</label>
                            <input type='text' name='rngprice' placeholder='0.00'><br><br>
                            <label for='deposit'>Deposit Payed: $</label>
                            <input type='text' name='deposit' placeholder='0.00'><br><br>
                            <label for='depositdue'>Deposit Due: $</label>
                            <input type='text' name='depositdue' placeholder='0.00'><br><br>
                            <label for='tradein'>Trade-In: </label>
                            <select name='tradein' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br><br>
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
                                <option value='' selected>-</option>
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
                    <header class='main'>Home Information<img id='arrow' class='arrow' src='images/triangle-arrow.png' alt=''></header><br><br> 
                    <div class='container'>
                        <div class='column'>                          
                            <label for='year'>Year: </label>
                            <input type='text' name='year'><br><br>
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
                                <option value='' selected>-</option>
                                <option value='A277'>Residential</option>
                                <option value='Z240'>Manufactured</option>
                            </select><br><br>
                            <label for='serial'>Serial Number: </label>
                            <input type='text' name='serial'><br><br>
                            <label for='mhr'>MHR Number: </label>
                            <input type='text' name='mhr'><br><br>
                            <label for='sections'>Sections: </label>
                            <select name='sections' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='double'>Double Section</option>
                                <option value='single'>Single Section</option>
                            </select><br><br>
                        </div>
                    </div>
                </div>

                <div class='field' id='setup'>
                    <header class='main'>Site Setup Information<img id='arrow' class='arrow' src='images/triangle-arrow.png' alt=''></header><br><br>
                    <div class='container'>
                        <div class='column'>
                            <header class='sub'>Setup Info</header><br><br>
                            <label for='sitechk'>Site Check: </label>
                            <select name='sitechk' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                            <label for='drywall'>Drywall Onsite: </label>
                            <select name='drywall' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                            <label for='fiber'>Fiber Cement: </label>
                            <select name='fiber' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                            <label for='skirting'>Skirting: </label>
                            <select name='skirting' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                                <option value='x'>Fiber Cement</option>
                            </select><br><br>
                            <label for='stairs'>Stairs: </label>
                            <select name='stairs' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value='x'>No</option>
                            </select><br><br>
                        </div>

                        <div class='column'>
                            <header class='sub'>Roof Info</header><br><br>
                            <label for='regular'>Regular Pitch: </label>
                            <select name='regular' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='std'>Standard</option>
                                <option value='4/12'>4/12</option>
                            </select><br><br>
                            <label for='lrg'>Large Pitch: </label>
                            <select name='lrg' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='5/12'>5/12</option>
                                <option value='6/12'>6/12</option>
                            </select><br><br>
                            <label for='roofonsite'>Roof Pitch Onsite: </label>
                            <select name='roofonsite' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='incl'>Included</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select><br><br>
                            <label for='dormer'>Dormer Onsite: </label>
                            <select name='dormer' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                            <label for='solar'>Solar Tubes Onsite: </label>
                            <select name='solar' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                            <label for='skylight'>Skylight Onsite: </label>
                            <select name='skylight' form='mstrform'>
                                <option value='' selected>-</option>
                                <option value='x'>Yes</option>
                                <option value=''>No</option>
                            </select><br><br>
                        </div>
                    </div>
                </div>

                <div class='field'  id='forms'>
                    <header class='main'>Applicable Forms</header><br><br>
                    <div class='column'>
                        <input type='checkbox' name='sales_contract' value='true' checked>
                        New Manufactured Home Sales Agreement<br><br>
                        <input type='checkbox' name='gst_new_housing' value='true' checked>
                        GST/HST New Housing Rebate Application<br><br>
                        <input type='checkbox' name='bill_of_sale' value='true' checked>
                        Bill of Sale<br><br>
                        <input type='checkbox' name='payment_cash' value='true' checked>
                        Payment Method Cash<br><br>
                        <input type='checkbox' name='payment_finance' value='true' checked>
                        Payment Method Finance<br><br>        
                        <input type='checkbox' name='individual_id_record' value='true' checked>
                        Individual Identification Record<br><br>                                             
                    </div>
                    <div class='column'>
                        <input type='checkbox' name='residential_set_up' value='true' checked>
                        Residential Series Home Set Up Sheet<br><br>
                        <input type='checkbox' name='home_set_up_sheet' value='true' checked>
                        Manufactured Home Set Up Sheet<br><br> 
                        <input type='checkbox' name='site_check_req' value='true' checked>
                        Site Check Request From<br><br>
                        <input type='checkbox' name='appliance_delivery_waiver' value='true' checked>
                        Appliance Delivery Waiver<br><br>
                        <input type='checkbox' name='linoleum_waiver' value='true' checked>
                        Linoleum Flooring Waiver<br><br>
                        <input type='checkbox' name='addendum_2' value='true' checked>
                        Addendum #2<br><br>
                    </div>
                </div>
                <button type='submit' value='submit' id='generate' onclick='clicked(event)'>Generate Forms</button>
            </form>";
        echo $display_block;
        ?>
    </body>
</html>