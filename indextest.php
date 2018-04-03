<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Eagle Homes Form Auto-fill</title>
    <h1>Eagle Homes Form Auto-Filler</h1>
    </head>
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
            width: 56%;
        }
        
        a {
            text-decoration: none;
            color: black;
            font-size: 130%;
            font-weight: bold;
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
        
        header {
            display: inline-block;
            margin-bottom: 0.55em;
            float: left;
            clear: right;
            font-size: 115%;
        }
        
        label, input {
            display: inline-block;
            vertical-align: middle;
            padding: 0.2em;
        }
        
        label {
            float: left;
            margin-right: 0.5em;
        }
        
        input {
            width: 200px;
            font-size: 100%;
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
        
        @media only screen and (max-width: 1500px) {
            .column {
                width: 100%;
            }
            
            body {
                width: 66%;
            }
        }
        
    </style>
    <body>
        <?php
        session_start();
        $display_block = "
            <form method='post' action='formtest.php'>
                <div class='field'>
                    <header><a href='#clientsearch' title='clientsearch'>Search for a Client</a></header><br><br>
                    <label for='lastname'>Last Name: </label>
                    <input type='text' id='lastname' name ='lastname'><br><br>
                    <label for='firstname'>First Name: </label>
                    <input type='text' id='firstname' name ='firstname'><br><br>
                    <label for='email'>Email: </label>
                    <input type='text' id='email' name ='email'><br><br>
                </div>
                <button type='submit' value='search' id='searchbtn'>Search</button>
             </form>";
        echo $display_block;
        ?>
    </body>
</html>
