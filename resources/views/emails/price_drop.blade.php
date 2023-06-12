<!DOCTYPE html>
<html>
<head>
    <title>Thriftly.com</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        *{
            box-sizing: border-box;
        }        
        body {
            margin: 0px;
            padding: 0px;
            background:#f3f3f3;
            box-sizing: border-box;
            font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
            color:#555555;
        }
        table tr td {
            font-family: 'Helvetica',Helvetica Neue,Arial,sans-serif;
            font-size: 16px;
        }        
        table tr td b {
        }
        img {
            max-width: 100%;
        }
    </style>
</head>
<body>
    <table style=" width: 100%;
            font-size: 16px;
            border-collapse: collapse;
            font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
            background: #F3F3F5;
            margin: 0 auto;
            line-height: 1.3;
            white-space: normal;
            word-break: break-word;">
        <tr>
            <td style="padding:30px 0">
                <table style="
                                width: 100%;
                                font-size: 16px;
                                border-collapse: collapse;
                                font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
                                background: #ffffff;
                                max-width: 700px;
                                margin: 0 auto;
                                line-height: 1.3;
                                white-space: normal;
                                word-break: break-word;
                                width: 95%;">
                    <tr>
                        <td  style="padding:25px 15px 15px; border-top: 2px solid #86d32d;">
                            <table class="tableThree" style="
                            width: 100%;
                            font-size: 16px;
                            border-collapse: collapse;
                            font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
                            max-width: 600px;margin: 0 auto;">
                                <tr>
                                    <td>
                                        <table style="
                                        width: 100%;
                                        font-size: 16px;
                                        border-collapse: collapse;
                                        font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;">
                                            <tr><td  style="padding:10px 0 20px; font-size: 22px;">Dear <strong style="font-weight: 600;">{{$customer_name}}</strong>,</td></tr>
                                            <tr>
                                                <td style="padding:10px 0;">
                                                    An item on your watch list has just dropped in price. Don't miss out on this limited chance!
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0;">
                                                    <div class="width:100%; overflow:auto;">
                                                    <table style=" width: 200px; margin:0 auto; font-size: 16px;border-collapse: collapse; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; border:1px solid #ddd;">
                                                        <tr>
                                                            <td style="padding: 10px; border:1px solid #ddd;">
                                                                <a href="https://thriftlydotcom.myshopify.com/products/{{$data['handle']}}" target="_blank" style="display: block;  text-decoration: none; text-align: center;">
                                                                    <img src="{{$data['image']['src']}}" align="NF" style="width: 200px;display: inline-block;margin: 5px 0 0; max-width:100%;">
                                                                    <h6 style="margin: 5px 0 0px;font-size: 14px;text-transform: uppercase; color: #69a040;">{{$data['title']}}</h6>
                                                                    <div style="display: block;margin: 5px 0 0; color: #777777; text-decoration: line-through;">Previous Price: ${{$data['variants'][0]['price']}}</div>
                                                                    <div style="display: block;margin: 5px 0 0; color: red;">New Price: ${{$data['new_price']}}</div>
                                                               </a>
                                                            </td>
                                                        </tr>
                                                        
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; text-align: center;">
                                                    Act fast because this is the only one in stock!
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0;">Act quickly to secure the item from your watchlist, as it's in high demand and won't last long.</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0;">Happy shopping, and thank you for being a valued member of our community &#128522;</td>
                                            </tr>
                                            
                                            <tr><td style="padding-top: 30px;">Kindest regards,</td></tr>
                                            <tr><td>Thriftly.com</td></tr>
                                            <tr><td class="pb-30" style="padding-bottom: 30px;">
                                                <img src="https://cdn.shopify.com/s/files/1/0734/2601/0431/t/3/assets/staticlogo_270x.png?v=107154510693551407981679205169" align="NF" style="max-width:100%;width: 200px;display: block;margin: 5px 0 0;">
                                                </td>
                                            </tr>


                                            <tr>
                                                <td style="font-weight: 600; text-align: center; padding:10px 0 20px;">Checkout some other Other item’s we’ve picked out for you to Thriftly.com</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 0 40px;">
                                                    <div class="width:100%; overflow:auto;">
                                                    <table style=" min-width: 550px; width: 100%;font-size: 16px;border-collapse: collapse; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; border:1px solid #ddd;">
                                                        <tr>
                                                        @foreach($new_arraival as $key => $arraival)
                                                            @if($key < 4) 
                                                            <td style="padding: 10px; border:1px solid #ddd; width:150px; vertical-align: top;">
                                                                <a href="https://thriftlydotcom.myshopify.com/products/{{$arraival['handle']}}" style="display: block;  text-decoration: none; text-align: center;">
                                                                    <img src="{{$arraival['image']['src']}}" align="NF" style="width: 200px;display: inline-block;margin: 5px 0 0; max-width:100%; ">
                                                                    <h6 style="margin: 5px 0 0px;font-size: 14px;text-transform: uppercase; color: #69a040;">{{$arraival['title']}}</h6>
                                                                   <div style="display: block;margin: 5px 0 0; color: #000;">${{$arraival['variants'][0]['price']}}</div>
                                                               </a>
                                                            </td>
                                                            @endif
                                                        @endforeach
                                                        </tr>
                                                        
                                                    </table>
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
