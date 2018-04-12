<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
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
                margin-top: 0.5em;
                margin-bottom: 1.5em;
                border-style: none;
                box-shadow: 5px 5px 8px #3d3d3d;
                padding: 2.5em 2em 1em 2em;
                width: 100%;
                display: table;
            }

            header {
                display: inline-block;
                margin: 0em 0em 1.3em 0.2em;
                float: left;
                width: 100%;
                height: 100%;
                font-size: 140%;
                font-weight: bold;
                cursor: pointer;
            }

            .column {
                width: 40%;
                float: left;
                display: block;
            }

            label, input {
                display: inline-block;
            }

            label + input {
                width: 60%;
                margin: 0 5% 0 2%;
            }

            label {
                width: 30%;
                text-align: right;
            }

            label, input, select {
                display: inline-block;
            }

            input {
                width: 200px;
                font-size: 100%;
                float: right;
                border-radius: 2px;
                border-color: #777777; 
                border-style: solid;
                border-width: 1px;
            }

            #skipbtn {
                background-color: #00478e;
                color: #FFFFFF;
                border-style: none;
                border-radius: 2px;
                font-size: 100%;
                padding: 10px 15px;
                margin: 0.3em -3.2em 1.5em 0em;
                box-shadow: 5px 5px 8px #3d3d3d;
                text-decoration: none;
                display: inline-block;
                float: right;
            }

            #searchbtn {
                background-color: #00478e;
                color: #FFFFFF;
                border-style: none;
                border-radius: 2px;
                font-size: 100%;
                padding: 10px 15px;
                margin: 0.3em 1em 1.5em 0em;
                box-shadow: 5px 5px 8px #3d3d3d;
                text-decoration: none;
                display: inline-block;
                float: right;
            }
        
        @media only screen and (max-width: 1300px) {
            
            .column {
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
    
    <body>
        <?php
        session_start();
        $display_block = "
            <form method='POST' action='formtest.php'>
                <div class='field'>
                    <header>Search for a Client</header><br><br>
                    <div class='column'>
                        <label for='lastname'>Last Name: </label>
                        <input type='text' id='lastname' name ='lastname'><br><br>
                        <label for='firstname'>First Name: </label>
                        <input type='text' id='firstname' name ='firstname'><br><br>
                        <label for='email'>Email: </label>
                        <input type='text' id='email' name ='email'><br><br>
                    </div>
                </div>
                <button type='submit' value='skip' id='skipbtn'>Skip</button>
                <button type='submit' value='search' id='searchbtn'>Search</button>
             </form>";
        echo $display_block;
        ?>
    </body>
</html>
