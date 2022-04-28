<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="{{ asset('static/user/lp/preview.css') }}" media="all">
  @if(!empty($styles))
    @foreach($styles as $index=>$style)
      <link rel="stylesheet" href="{{ $style }}" key="{{ $index }}"/>
    @endforeach
  @endif

  <title>{{ $title ?? '' }}</title>
</head>
<body>
<div id="nocode-main-container" data-device="{{ $deviceType ?? 'desktop' }}" data-search-link="{{ $searchLink ?? '' }}">
  @if(!empty($normalLayouts))
    @foreach($normalLayouts as $index=>$layout)
      <div class='nocode-editor-layout' key="{{ $layout['key'] }}">
        <div id="nocode-lp-{{ $layout['key'] }}"
             class="nocode-layout-{{ $layout['source']['id'] }}"
             nocode-data-layout-key="{{ $layout['key'] }}">
          <a id="nocode-lp-anchor-{{ $layout['key'] }}"></a>
          <div class="nocode-editor-layout-content">
            {!! $layout['content'] !!}
          </div>
        </div>
      </div>
    @endforeach
  @endif

  @if(!empty($popupLayouts))
    @foreach($popupLayouts as $index=>$layout)
      <div class="nocode-layout-popup"
           data-layout-key="{{ $layout['key'] }}"
           data-popup-type="{{ $layout['setting']['show']['type'] }}"
           data-popup-delay="{{ $layout['setting']['show']['type'] == 1 ? $layout['setting']['show']['data']['delay'] : '' }}"
           data-popup-offset="{{ $layout['setting']['show']['type'] == 2 ? $layout['setting']['show']['data']['offset'] : '' }}"
           data-popup-size="{{ $layout['setting']['show']['size'] }}">
        <div class="nocode-editor-popup-box {{ $deviceType }}-mode">
{{--          <div class="nocode-editor-popup-close">--}}
{{--            <span>X</span>--}}
{{--          </div>--}}
          <div class="nocode-editor-popup-layout"
               id="nocode-lp-{{ $layout['key'] }}"
               nocode-data-layout-key="{{ $layout['key'] }}">
            <div class="nocode-layout-{{ $layout['source']['id'] }}">
              {!! $layout['content'] !!}
            </div>
          </div>
        </div>
        <div class="nocode-editor-mask"></div>
      </div>
    @endforeach
  @endif

  <script src="{{ asset('static/user/lp/jquery-3.6.0.min.js') }}" type="text/javascript"></script>
  <script src="{{ asset('static/user/lp/preview.js') }}" type="text/javascript"></script>
  @if(!empty($scripts))
    @foreach($scripts as $index=>$script)
      <script type="text/javascript" src="{{ $script }}" key="{{ $index }}"></script>
    @endforeach
  @endif
</div>
</body>
</html>