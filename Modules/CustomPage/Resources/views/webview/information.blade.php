<!doctype html>
<html ⚡>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="This is the AMP Boilerplate.">
    <link rel="preload" as="script" href="https://cdn.ampproject.org/v0.js">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script>
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
        @foreach ($result['custom_page_image_header'] as $key => $value)
        <img src="{{config('url.storage_url_api')}}{{ $value['custom_page_image'] }}" />
        @endforeach
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

            @if (isset($result['custom_page_event_location_map']))
            <iframe
              width="100%"
              height="250px"
              style="border:0;margin: 15px 0;padding: 0;"
              loading="lazy"
              allowfullscreen
              src="https://www.google.com/maps/embed/v1/place?key=AIzaSyDLRQ1NMLsGi7RPXvlGmEV32SbpZ1vqFsg&q={{$result['custom_page_event_latitude']}},{{$result['custom_page_event_longitude']}}">
            </iframe>
            @endif

            <div class="description">
                {!! $result['custom_page_description'] !!}
            </div>

            <amp-carousel
              width="450"
              height="300"
              layout="responsive"
              type="slides"
              role="region"
              aria-label="Basic carousel"
            >
              <amp-img
                src="{{config('url.storage_url_api')}}{{ $value['custom_page_image'] }}"
                width="450"
                height="300"
              ></amp-img>
              <amp-img
                src="{{config('url.storage_url_api')}}{{ $value['custom_page_image'] }}"
                width="450"
                height="300"
              ></amp-img>
              <amp-img
                src="{{config('url.storage_url_api')}}{{ $value['custom_page_image'] }}"
                width="450"
                height="300"
              ></amp-img>
            </amp-carousel>

        </div>
  </body>
</html>