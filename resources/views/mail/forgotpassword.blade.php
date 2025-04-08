<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Activation Code</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        .header {
            background: #9128df;
            padding: 20px 0;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 40px;
            text-align: center;
        }
        .code-box {
            font-size: 24px;
            font-weight: bold;
            color: #9128df;
            margin: 30px 0;
            padding: 15px;
            background: #f8f0ff;
            border: 1px dashed #c084fc;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <center>
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center" style="padding: 40px 10px;">
                    <!-- Container -->
                    <table class="container" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <!-- Header -->
                        <tr>
                            <td class="header">
                                <img src="https://tucartaya.com/wp-content/uploads/2025/03/treggio-logo.png" alt="Logo">
                            </td>
                        </tr>

                        <!-- Content -->
                        <tr>
                            <td class="content">
                                <div style="font-size: 20px; margin-bottom: 10px;">Código:</div>
                                <div class="code-box">{{ $user->code_verified }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
