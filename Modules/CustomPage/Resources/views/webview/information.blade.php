<!doctype html>
<html ⚡>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="This is the AMP Boilerplate.">
    <link rel="preload" as="script" href="https://cdn.ampproject.org/v0.js">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script>
    <script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Gothic+A1&display=swap" rel="stylesheet">
    <!-- Import other AMP Extensions here -->
    <style amp-custom>
    /* Add your styles here */
    </style>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <style>
    html, body {
        width: 100vw;
        padding: 0;
        margin: 0;
        font-size: 14px;
        font-family: 'Gothic A1', sans-serif;
    }

    body {
        padding-bottom: 20px;
    }
    
    img {
        width: 100%;
    }

    .content {
        padding: 10px;
    }
    .content .title {
        font-size: 16px;
        margin-bottom: 5px;
    }
    .content .label {
        font-size: 13px;
        margin-bottom: 5px;
    }
    .content .content-value {
        font-size: 13px;
        margin-bottom: 5px;
    }

    .content .description {
        text-align: justify;
        margin-top: 15px;
        font-size: 14px;
    }
    </style>
    <link rel="canonical" href=".">
    <title>{{$result['custom_page_title']}}</title>
  </head>
  <body>
        @if (isset($result['custom_page_image_header']))
        <amp-carousel
          width="450"
          height="300"
          layout="responsive"
          type="slides"
          role="region"
          aria-label="Basic carousel"
        >
        @foreach ($result['custom_page_image_header'] as $key => $value)
            <div style="position: relative !important; width: 100vw !important;">
                <amp-img
                    src="{{config('url.storage_url_api')}}{{ $value['custom_page_image'] }}"
                    width="450"
                    height="300"
                    layout="responsive"
                    ></amp-img>
            </div>
        @endforeach
        </amp-carousel>
        @endif
        <div class="content">
            <h1 class="title">{{$result['custom_page_title']}}</h1>
            @if(isset($result['custom_page_event_date_start']))
                <h3 class="label">Periode</h3>
                @if (isset($result['custom_page_event_date_start']))
                    @if ($result['custom_page_event_date_start'] == $result['custom_page_event_date_end'])
                    <div class="content-value"> {{ date('d F Y', strtotime($result['custom_page_event_date_start'])) }}</div>
                    @else
                    <div class="content-value"> {{ date('d F', strtotime($result['custom_page_event_date_start'])) }} - {{ date('d F Y', strtotime($result['custom_page_event_date_end'])) }}</div>
                    @endif
                @endif
            @endif

            @if (isset($result['custom_page_event_time_start']))
                <h3 class="label">Waktu</h3>
                @if ($result['custom_page_event_time_start'] == $result['custom_page_event_time_end'])
                    <div class="content-value">{{ date('H:i', strtotime($result['custom_page_event_time_start'])) }}</div>
                @else
                    <div class="content-value">{{ date('H:i', strtotime($result['custom_page_event_time_start'])) }} - {{ date('H:i', strtotime($result['custom_page_event_time_end'])) }}</div>
                @endif
            @endif

            @if (isset($result['custom_page_event_location_name']))
                <h3 class="label">Lokasi</h3>
                <div class="content-value">{{$result['custom_page_event_location_name']}}</div>
            @endif

            @if (isset($result['custom_page_event_location_phone']))
                <h3 class="label">Hubungi</h3>
                <div class="content-value">{{$result['custom_page_event_location_phone']}}</div>
            @endif

            @if (isset($result['custom_page_event_location_address']))
                <h3 class="label">Alamat</h3>
                <div class="content-value">{{$result['custom_page_event_location_address']}}</div>
            @endif
            @if (isset($result['custom_page_event_latitude']) && isset($result['custom_page_event_longitude']))
                <amp-iframe
                  width="200"
                  height="100"
                  sandbox="allow-scripts allow-same-origin allow-popups-to-escape-sandbox allow-popups"
                  layout="responsive"
                  frameborder="0"
                  allowfullscreen
                  src="https://www.google.com/maps/embed/v1/place?key=AIzaSyDLRQ1NMLsGi7RPXvlGmEV32SbpZ1vqFsg&q={{$result['custom_page_event_latitude']}},{{$result['custom_page_event_longitude']}}"
                ></amp-iframe>
            @endif

            <div class="description">
                {!! $result['custom_page_description'] !!}
            </div>

        @if (isset($result['custom_page_video_text']) && isset($result['custom_page_video']))
            <h3 class="label">{{ $result['custom_page_video_text'] }}</h3>
            <amp-iframe
              width="200"
              height="100"
              sandbox="allow-scripts allow-same-origin allow-popups-to-escape-sandbox allow-popups"
              layout="responsive"
              frameborder="0"
              allowfullscreen
              src="https://www.youtube.com/embed/{{ substr($result['custom_page_video'], strpos($result['custom_page_video'], "=") + 1) }}?rel=0">
            </amp-iframe>
        @endif

        @if (isset($result['custom_page_outlet_text']) && isset($result['custom_page_outlet']))
            <h3 class="label">{{ $result['custom_page_outlet_text'] }}</h3>
            <amp-carousel
              width="450"
              height="200"
              layout="responsive"
              type="slides"
              role="region"
              aria-label="Basic carousel"
            >
            @foreach ($result['custom_page_outlet'] as $key => $value)
                <div style="position: relative !important; width: calc(100vw - 20px) !important;">
                    <amp-img
                        src="{{ isset($value['outlet']['outlet_image']) ? $value['outlet']['outlet_image'] : 'https://via.placeholder.com/450x300.png?text=No Image Available' }}"
                        width="450"
                        height="200"
                        layout="responsive"
                    ></amp-img>
                    <span style="background-color: rgba(0, 0, 0, 0.6); position: absolute; top: 0; right:0; left: 0; padding: 10px; color: white; font-weight:600">{{$value['outlet']['outlet_name']}}</span>
                </div>
            @endforeach
            </amp-carousel>
        @endif

        @if (isset($result['custom_page_product_text']) && isset($result['custom_page_product']))
            <h3 class="label">{{ $result['custom_page_product_text'] }}</h3>
            <amp-carousel
              width="450"
              height="450"
              layout="responsive"
              type="slides"
              role="region"
              aria-label="Basic carousel"
            >
            @foreach ($result['custom_page_product'] as $key => $value)
                <div style="position: relative !important; width: calc(100vw - 20px) !important;">
                    <amp-img
                        src="{{ isset($value['product']['photos'][0]['url_product_photo']) ? $value['product']['photos'][0]['url_product_photo'] : 'https://via.placeholder.com/800x500.png?text=No Image Available' }}"
                        width="450"
                        height="450"
                        layout="responsive"
                    ></amp-img>
                    <span style="background-color: rgba(0, 0, 0, 0.6); position: absolute; top: 0; right:0; left: 0; padding: 10px; color: white; font-weight:600">{{ $value['product']['product_name'] }}</span>
                </div>
            @endforeach
            </amp-carousel>
        @endif

        @if (isset($result['custom_page_button_form']))
        <div style="text-align: center; width: 100%; margin-top:10px">
            <a style="color:#ffffff; background-color: #000; width:  100% !important; border: 0; padding: 10px 0; font-size: 1.2em; display: block; text-decoration:none" id="action" href="{{$result['custom_page_button_form_text_value']}}">{{$result['custom_page_button_form_text_button']}}</a>
        </div>
        @endif

        </div>
  </body>
</html>