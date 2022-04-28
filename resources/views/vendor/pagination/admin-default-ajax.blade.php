@if ($paginator->hasPages())
  <span id="loading" style="display:none;"><img src="{{ asset('/static/admin/images/spinner.gif') }}" align="texttop"></span>
  <select style="width:50px;display:none;" onchange="paginateChangeSize(this, '{{ $paginator->url(1) }}')" updatebox="{{isset($paginationUpdateId) ? $paginationUpdateId : '#paginationUpdate' }}">
      <?php $pageSizeList = [10,20,30,40,50,100]; ?>
    @foreach ($pageSizeList as $pageSize)
      <option value="{{ $pageSize }}" @if ($pageSize == $paginator->perPage()) selected="selected" @endif>{{ $pageSize }}</option>
    @endforeach
  </select>
  &nbsp;&nbsp;
  {{-- Previous Page Link --}}
  @if ($paginator->onFirstPage())
    <a href="javascript:void(0)" class="common_pager_prev unactive"></a>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" class="common_pager_prev active" onclick="paginatePageClick(this);return false;"></a>
  @endif

  {{-- Pagination Elements --}}
  <ul class="common_pager_list">
  @foreach ($elements as $element)
    {{-- "Three Dots" Separator --}}
    @if (is_string($element))
      <span>{{ $element }}</span>
    @endif

    {{-- Array Of Links --}}
    @if (is_array($element))
      @foreach ($element as $page => $url)
        <li class="common_pager_item">
        @if ($page == $paginator->currentPage())
          <a href="javascript:void(0)" class="common_pager_link active"><span>{{ $page }}</span></a>
        @else
          <a href="{{ $url }}" class="common_pager_link" onclick="paginatePageClick(this);return false;"><span>{{ $page }}</span></a>
        @endif
        </li>
      @endforeach
    @endif
  @endforeach
  </ul>

  {{-- Next Page Link --}}
  @if ($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" class="common_pager_next active" onclick="paginatePageClick(this);return false;"></a>
  @else
    <a href="javascript:void(0)" class="common_pager_next unactive"></a>
  @endif
@endif
