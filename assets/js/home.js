var XSRF = getCookie('__xsrf');
var VERSION = document.querySelector('html').getAttribute('version');
var CLIPBOARD = false;
var DO_ACTION = false;
var MAX_UPLOAD_SIZE = 0 | parseInt(document.querySelector('.maxSize').innerHTML);
var menu_options = document.querySelector('.options');

(function ($) {
  $.fn.autoSort = function () {
    var $table = this;
    var fe_sort = (getCookie('fe_sort') ? getCookie('fe_sort') : '0|sort_asc').split('|');

    if (fe_sort.length) {
      $table.sortBy(parseInt(fe_sort[0]), (fe_sort[1] == 'sort_desc'));
    }

    this.find('.tH').click(function () {
      $table.sortBy($(this).index(), $(this).hasClass('sort_asc'));
    });
    return this;
  }
  $.fn.sortBy = function (idx, direction) {
    var sBy = direction ? 'sort_desc' : 'sort_asc';

    setCookie('fe_sort', idx + '|' + sBy, 30);

    function data_sort(a) {
      var a_val = $(a).find('.tD:nth-child(' + (idx + 1) + ')').attr('data-sort');
      return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
    }
    this.find('.tH').removeClass('sort_asc sort_desc');
    this.find('.tHead .tH:eq(' + idx + ')').addClass(sBy);

    $rows = this.find('.item').not('.tHead');
    $rows.sort(function (a, b) {
      var a_val = data_sort(a),
        b_val = data_sort(b);
      return (a_val < b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
    });
    for (var i = 0; i < $rows.length; i++)
      this.append($rows[i]);
    return this;
  }

  $list = $('main');
  $(window).on('hashchange', list).trigger('hashchange');
  $('input').prop('autocomplete', 'off').prop('spellcheck', false);

  $(document).on('click', '.refresh', function (e) {
    CLIPBOARD = false;
    DO_ACTION = false;
    modal('off');
    hide_option_menu();
    $('.toast').css('opacity', 0);
    $(window).trigger('hashchange');
  });


  /* DRAG DROP and CHOOSE FILES
   *******************************************/
  document.querySelector('.maxSize').innerHTML = formatFileSize(MAX_UPLOAD_SIZE);
  $(window).on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    modal('on', '#uploadModal');
  });

  $(window).on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    modal('off');
  });

  $('#drop_area').on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('hover');
  });

  $('#drop_area').on('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('hover');
  });

  $('#drop_area').on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('hover');
    var files = e.originalEvent.dataTransfer.files;

    files.length && modal('on', '#progressModal');
    $.each(files, function (index, file) {
      uploadFile(file, index);
    });
  });

  $('input[type=file]').change(function (e) {
    e.preventDefault();
    this.files.length && modal('on', '#progressModal');

    $.each(this.files, function (index, file) {
      uploadFile(file, index);
    });
  });



  /* UPLOADING FILES
   *******************************************/
  function uploadFile(file, index) {
    $modal = $('#progressModal');
    var folder = decodeURIComponent(window.location.hash.substr(1));
    if (file.size > MAX_UPLOAD_SIZE) {
      $modal.find('.body').append(renderFileError(file, index));
      return false;
    }

    var len = $modal.find('.uploading').length;
    docTitle(len + ' uploads in progress');
    $modal.find('.title').text(document.title);
    $modal.find('.body').append(renderFileUpload(file, index));

    var fd = new FormData();
    fd.append('do', 'upload');
    fd.append('file_data', file);
    fd.append('path', folder);
    fd.append('xsrf', XSRF);
    var XHR = new XMLHttpRequest();
    XHR.open('POST', '');

    XHR.upload.onprogress = function (e) {
      if (e.lengthComputable) {
        var progress = e.loaded / e.total * 100 | 0;
        if (progress == 100)
          $modal.find('li.upload_' + index).attr('title', 'Finalizing...').find('.progress span').css('width', '100%');
        else
          $modal.find('li.upload_' + index).attr('title', 'Uploading ' + progress + '%').find('.progress span').css('width', progress + '%');
      }
    };

    XHR.upload.onload = function () {
      $modal.find('li.upload_' + index).removeClass('uploading').removeAttr('title');

      var len = $modal.find('.uploading').length;
      docTitle(len + ' uploads in progress');
      $modal.find('.title').text(document.title);
      if (len < 1) {
        window.setTimeout(function () {
          list();
          docTitle();
          modal('off');
          toast('Files Uploaded Successfully', TOAST_SUCCESS_COLOR);
          $modal.find('.body').empty();
        }, 2000);
      }
    };

    XHR.upload.onabort = function () {
      docTitle('Files Aborted');
      $modal.find('li.uploading.upload_' + index).addClass('error').removeClass('uploading').removeAttr('title', 'Upload Aborted');

      window.setTimeout(function () {
        list();
        docTitle();
        modal('off');
        toast('Files Aborted', '#B00');
        $modal.find('.body').empty();
      }, 5000);
    };

    XHR.send(fd);

    $(document).on('click', '#progressModal button', function (e) {
      XHR.abort();
    });
  }

  function docTitle(str = null) {
    document.title = typeof str == 'string' ? str : 'File Explorer v' + VERSION;
  }

  function renderFileUpload(file, index) {
    return $('<li class="upload_' + index + ' uploading">')
      .attr('title', 'Starting Upload...')
      .attr('data-size', formatFileSize(file.size))
      .append($('<label>').text(file.name))
      .append($('<div class="progress"><span></span></div>'))
  }

  function renderFileError(file, index) {
    return $('<li class="upload_' + index + ' error">')
      .attr('title', 'Exceeds max upload size of ' + formatFileSize(MAX_UPLOAD_SIZE))
      .attr('data-size', formatFileSize(file.size))
      .append($('<label>').text(file.name))
      .append('<div class="progress"><span style="width: 100%;"></span></div>')
  }



  /* CREATE NEW DIRECTORY
   *******************************************/
  $('#newDirModal form').submit(function (e) {
    $form = $(this);
    e.preventDefault();
    var HASHVAL = decodeURIComponent(window.location.hash.substr(1));
    var dirname = $form.find('#dirname').val().trim();
    toast('Creating...', '', 'wait');

    dirname.length && $.post('', {
      do: 'mkdir',
      dirname: dirname,
      path: HASHVAL,
      xsrf: XSRF
    }, function (data) {
      list();
      modal('off');
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
      $form.find('input').val('');
    }, 'json');
  });



  /* CREATE NEW FILE
   *******************************************/
  $('#newFileModal form').submit(function (e) {
    $form = $(this);
    e.preventDefault();
    var HASHVAL = decodeURIComponent(window.location.hash.substr(1));
    var filename = $form.find('#filename').val().trim();
    toast('Creating...', '', 'wait');

    filename.length && $.post('', {
      do: 'nwfile',
      filename: filename,
      path: HASHVAL,
      xsrf: XSRF
    }, function (data) {
      list();
      modal('off');
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
      $form.find('input').val('');
    }, 'json');
  });



  /* RENAME FILE and FOLDER
   *******************************************/
  $(document).on('click', '.rename', function (e) {
    var path = $(this).closest('.options[data-real_path]').attr('data-real_path').trim();
    var name = $(this).closest('.options[data-name]').attr('data-name').trim();

    modal('on', '#renameModal');
    $modal = $('#renameModal');
    $modal.find('#path').val(path);
    $modal.find('#newname').val(name).attr('placeholder', name).focus();
  });

  $('#renameModal form').submit(function (e) {
    $form = $(this);
    e.preventDefault();

    var path = $form.find('#path').val().trim();
    var newn = $form.find('#newname').val().trim();
    toast('Renaming...', '', 'wait');

    path.length && newn.length && $.post('', {
      do: 'rename',
      newname: newn,
      path: path,
      xsrf: XSRF
    }, function (data) {
      list();
      modal('off');
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
    }, 'json');
  });




  /* COPY and MOVE FILES
   *******************************************/
  $(document).on('click', '.copy', function (e) {
    CLIPBOARD = [];
    DO_ACTION = 'copy';

    $('main .selected').each(function () {
      CLIPBOARD.push($(this).find('a[data-real_path]').attr('data-real_path').trim());
    });

    !CLIPBOARD.length && CLIPBOARD.push($(this).closest('.options[data-real_path]').attr('data-real_path').trim());

    hide_option_menu();
    toast('Choose Copy Location', '', 'stay');
  });

  $(document).on('click', '.move', function (e) {
    CLIPBOARD = [];
    DO_ACTION = 'move';

    $('main .selected').each(function () {
      CLIPBOARD.push($(this).find('a[data-real_path]').attr('data-real_path').trim());
    });

    !CLIPBOARD.length && CLIPBOARD.push($(this).closest('.options[data-real_path]').attr('data-real_path').trim());

    hide_option_menu();
    toast('Choose Move Location', '', 'stay');
  });

  $(document).on('click', '.paste', function (e) {
    var HASHVAL = decodeURIComponent(window.location.hash.substr(1));
    hide_option_menu();

    if (DO_ACTION == 'copy') {
      toast('Copying...', '', 'wait');
    } else if (DO_ACTION == 'move') {
      toast('Moving...', '', 'wait');
    }

    $.post('', {
      do: DO_ACTION,
      ways: CLIPBOARD,
      path: HASHVAL,
      xsrf: XSRF
    }, function (data) {
      list();
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
      CLIPBOARD = false;
      DO_ACTION = false;
    }, 'json');
  });




  /* DELETE FILE
   *******************************************/
  $(document).on('click', '.delete', function () {
    CLIPBOARD = [];
    DO_ACTION = 'trash';

    $('main .selected').each(function () {
      CLIPBOARD.push($(this).find('a[data-real_path]').attr('data-real_path').trim());
    });

    !CLIPBOARD.length && CLIPBOARD.push($(this).closest('.options[data-real_path]').attr('data-real_path').trim());

    hide_option_menu();
    if (confirm('Do you want to Delete it ?')) {
      toast('Deleting...', '', 'wait');

      var HASHVAL = decodeURIComponent(window.location.hash.substr(1));
      $.post('', {
        do: DO_ACTION,
        ways: CLIPBOARD,
        path: HASHVAL,
        xsrf: XSRF
      }, function (data) {
        list();
        toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
        CLIPBOARD = false;
        DO_ACTION = false;
      }, 'json');
    } else {
      toast('Oh! Thanks God all is safe.');
    }
  });

  /* COMPRESS DIRECTORY
   *******************************************/
  $(document).on('click', '.cmprss', function () {
    modal('off');
    toast('Compressing...', '', 'wait');
    var path = $(this).closest('.options[data-path]').attr('data-path').trim();

    $.post('', {
      do: 'compress',
      path: path,
      xsrf: XSRF
    }, function (data) {
      if (data.flag == true) {
        list();
        toast(data.response, TOAST_SUCCESS_COLOR);
      } else {
        toast(data.response, '#B00');
      }
    }, 'json');
  });




  /* EXTRACT ZIP FILE
   *******************************************/
  $(document).on('click', '.extrct', function () {
    modal('off');
    toast('Extracting...', '', 'wait');
    var path = $(this).closest('.options[data-path]').attr('data-path').trim();

    $.post('', {
      do: 'extract',
      path: path,
      xsrf: XSRF
    }, function (data) {
      if (data.flag == true) {
        list();
        toast(data.response, TOAST_SUCCESS_COLOR);
      } else {
        toast(data.response, '#B00');
      }
    }, 'json');
  });



  /* CHANGE PERMISSIONS
   *******************************************/
  $(document).on('change', '#permitModal input[type=checkbox]', function () {
    var perm = 0;
    perm += $('#ownRead').prop('checked') ? 256 : 0;
    perm += $('#ownWrit').prop('checked') ? 128 : 0;
    perm += $('#ownExec').prop('checked') ? 64 : 0;
    perm += $('#grpRead').prop('checked') ? 32 : 0;
    perm += $('#grpWrit').prop('checked') ? 16 : 0;
    perm += $('#grpExec').prop('checked') ? 8 : 0;
    perm += $('#pubRead').prop('checked') ? 4 : 0;
    perm += $('#pubWrit').prop('checked') ? 2 : 0;
    perm += $('#pubExec').prop('checked') ? 1 : 0;

    $('#perm_code').val('0' + perm.toString(8));
  });

  $(document).on('paste keyup keydown click', '#perm_code', function () {
    var val = 0 | parseInt($(this).val().trim(), 8);

    $('#ownRead').prop('checked', !!(256 & val));
    $('#ownWrit').prop('checked', !!(128 & val));
    $('#ownExec').prop('checked', !!(64 & val));
    $('#grpRead').prop('checked', !!(32 & val));
    $('#grpWrit').prop('checked', !!(16 & val));
    $('#grpExec').prop('checked', !!(8 & val));
    $('#pubRead').prop('checked', !!(4 & val));
    $('#pubWrit').prop('checked', !!(2 & val));
    $('#pubExec').prop('checked', !!(1 & val));
  });

  $(document).on('change', '#perm_recursive_chk', function () {
    $('#permitModal input[type=radio]').prop('disabled', !$(this).prop('checked'));
  });

  $(document).on('click', '.permit', function (e) {
    $modal = $('#permitModal');
    var path = $(this).closest('.options[data-path]').attr('data-path');
    var perm = $(this).closest('.options[data-perm]').attr('data-perm');
    var is_dir = $(this).closest('.options[data-is_dir]').attr('data-is_dir') == 'true';

    $modal.find('#perm_path').val(path);
    $modal.find('#perm_code').val(perm).trigger('keydown');
    $modal.find('.inputs.recurse').prop('hidden', !is_dir);
    modal('on', '#permitModal');
  });

  $('#permitModal form').submit(function (e) {
    e.preventDefault();
    var path = $(this).find('#perm_path').val();
    var perm = $(this).find('#perm_code').val();
    var rcrs = $(this).find('[name="recurse"]:checked').val();
    rcrs = typeof rcrs == 'string' ? rcrs : '';
    toast('Changing...', '', 'wait');

    path.length && perm.length && $.post('', {
      do: 'permit',
      path: path,
      perm: perm,
      recurse: rcrs,
      xsrf: XSRF
    }, function (data) {
      list();
      modal('off');
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00');
      $('#perm_recursive_chk').prop('checked', false);
      $('[name="recurse"]').prop('checked', false).prop('disabled', false);
    }, 'json');
  });




  /* VIEW DETAILS
   *******************************************/
  $(document).on('click', '.info', function () {
    var obj = {};

    $.each($(this).closest('.options')[0].attributes, function (index, attr) {
      if (attr.name.indexOf('data-') > -1) {
        obj[attr.name.replace('data-', '')] = attr.value;
      }
    });
    modal('on', '#detailModal');
    var originPathName = window.location.origin + window.location.pathname;
    $modal = $('#detailModal');
    $modal.find('.name').text(obj.name);
    $modal.find('.path').text(obj.path);
    $modal.find('.type').text(obj.type);
    $modal.find('.size').text(obj.is_dir == 'true' ? 'Folder' : formatFileSize(obj.size));
    $modal.find('.ownr').text(obj.ownr_ok + ' (' + obj.ownr + ')');
    $modal.find('.perm').text(formatFilePerm(obj.perm, obj.is_dir == 'true') + ' (' + obj.perm + ')');
    $modal.find('.atime').text(timedate(obj.atime));
    $modal.find('.ctime').text(timedate(obj.ctime));
    $modal.find('.mtime').text(timedate(obj.mtime));
  });


  /* TOGGLE VIEW
   *******************************************/
  $(document).on('click', '.toggle_view', function (e) {
    e.preventDefault();
    var fe_view = $('main').hasClass('listView') ? 'gridView' : 'listView';
    var vw_text = $('main').hasClass('listView') ? 'List View' : 'Grid View';

    $(this).attr('title', vw_text);
    $('body').addClass('loading');
    setCookie('fe_view', fe_view, 30);
    setTimeout(function () {
      $('main').attr('class', fe_view);
      list();
    }, 500);
  });


  /* PASSWORD and SETTINGS PANEL
   *******************************************/
  $('#configModal .pwdeye').on('click', function (e) {
    e.preventDefault();
    $(this).toggleClass('off');
    $pass = $('#configModal #pass');
    $pass.attr('type') == 'password' ? $pass.attr('type', 'text') : $pass.attr('type', 'password');
  });

  $('#configModal form').submit(function (e) {
    e.preventDefault();
    var hdfl = $(this).find('#hdfl').prop('checked').toString();
    var pass = $(this).find('#pass').val().trim();
    toast('Updating Settings...', '', 'wait');

    $.post('', {
      do: 'config',
      hdfl: hdfl,
      pass: pass,
      xsrf: XSRF
    }, function (data) {
      modal('off');
      toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00', 'stay');
      window.setTimeout(function () {
        window.location.reload();
      }, 1000);
    }, 'json');
  });




  /* LOGOUT SESSION AND COOKIE
   *******************************************/
  $(document).on('click', '.logout', function () {
    modal('off');

    if (confirm('Are you sure to logout this session?')) {
      toast('Please Wait...', '', 'stay');

      $.post('', {
        do: 'logout',
        xsrf: XSRF
      }, function (data) {
        modal('off');
        toast(data.response, data.flag == true ? TOAST_SUCCESS_COLOR : '#B00', 'stay');
        window.setTimeout(function () {
          window.location.reload();
        }, 1000);
      }, 'json');
    }
  });




  /* UPDATE CORE VERSION
   *******************************************/
  $(document).on('click', '.upgrade', function () {
    modal('off');

    if (confirm('Are you sure to upgrade?')) {
      toast('Upgrading...', '', 'stay');

      $.post('', {
        do: 'upgrade',
        xsrf: XSRF
      }, function (data) {
        if (data.flag == true) {
          toast(data.response, TOAST_SUCCESS_COLOR, 'stay');
          window.setTimeout(function () {
            window.location.reload();
          }, 1000);
        } else {
          toast(data.response, '#B00', 10000);
        }
      }, 'json');
    }
  });




  /* MULTI SELECTION
   *******************************************/
  $(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.keyCode == 65) {
      e.preventDefault();
      $('main .item').addClass('selected');
    }
    if (e.keyCode == 27) {
      e.preventDefault();
      if ($('.modal.on').not('#progressModal').length) {
        modal('off');
      } else {
        $('main .item').removeClass('selected');
      }
    }
  });

  $(document).on('click', '.item', function (e) {
    e.preventDefault();
    if (e.ctrlKey || e.metaKey) {
      $(this).toggleClass('selected');
    } else if (e.shiftKey) {
      $(this).toggleClass('selected');
      if ($('main .selected').length > 1) {
        $('main .selected:first').nextUntil('main .selected:last').addClass('selected');
      }
    } else {
      $('main .item').removeClass('selected');
    }
  });

  $(document).on('click', '.item a', function (e) {
    e.preventDefault();
  });

  $(document).on('dblclick', '.item a', function (e) {
    var href = $(this).attr('href');
    if ($(this).hasClass('is_dir')) {
      var urlhash = window.location.hash; //get the hash from url
      let url = ''; 
      if(!urlhash) {
        url = href;
      }
      else {
        url = urlhash + '/' + href.replace("#", "");
      }
      window.location.href = url;
    } else {
      window.open(href, '_blank');
    }
    return false;
  });




  /* MENUS BEHAVIOUR
   *******************************************/
  $(window).on('focus', hide_option_menu);
  $(document).on('click', 'main .item a  span', hide_option_menu);
  $(document).on('click', 'main .item a .icon', hide_option_menu);
  $(document).on('click', 'main .item a .more', itemContextMenu);
  $(document).on('contextmenu', 'main .item a', itemContextMenu);
  $(document).on('contextmenu', function (e) {
    e.preventDefault();
  });

  $(document).on('click', function (e) {
    $container = $('.options');
    // ONLY if not clicked on self AND not clicked with in container AND not clicked with in card
    if (!$container.is(e.target) && !$container.has(e.target).length && !$('main .item a').has(e.target).length) {
      hide_option_menu();
    }

    if (!$('main .item').has(e.target).length) {
      $('main .item').removeClass('selected');
    }
  });

  $(document).on('contextmenu', function (e) {
    e.preventDefault();
    var opt = '';
    var is_selected = !!$('main .selected').length;

    opt += '<a class="refresh" title="Refresh">Refresh</a>';
    opt += '<a onclick="modal(\'on\', \'#uploadModal\' )" title="Upload">Upload</a>';
    opt += '<a onclick="modal(\'on\', \'#newDirModal\' )" title="New Folder">Create Folder</a>';
    opt += '<a onclick="modal(\'on\', \'#newFileModal\')" title="New File">Create File</a>';

    if (is_selected) opt += '<a class="copy" title="Copy">Copy</a>';
    if (is_selected) opt += '<a class="move" title="Move">Move</a>';
    if (is_selected) opt += '<a class="delete" title="Delete">Delete</a>';
    if (CLIPBOARD) opt += '<a class="paste" title="Paste">Paste</a>';

    // ONLY if clicked with <main> tag AND not clicked with in item or modal
    if (!($('.item').has(e.target).length || $('.modal.on').has(e.target).length)) {
      show_option_menu(e, opt);
    }
  });

  function itemContextMenu(e) {
    e.preventDefault();
    var obj = {};
    var opt = '';
    var is_selected = !!$('main .selected').length;
    $item = $(this).closest('.item');
    $item.addClass('hover').siblings().not('.tHead').removeClass('hover');

    $.each($item.find('a')[0].attributes, function (index, attr) {
      if (attr.name.indexOf('data-') > -1) {
        menu_options.setAttribute(attr.name, attr.value);
        obj[attr.name.replace('data-', '')] = attr.value;
      }
    });
    // MENU OPTIONS NOTE
    var menu = {
      open: '<a href="#' + obj.path + '" title="Open">Open</a>',
      runit: '<a href="' + obj.path + '" target="_blank" title="View">View</a>',
      dwnld: '<a href="?do=download&size=' + obj.size + '&path=' + encodeURIComponent(obj.real_path) + '" title="Download">Download</a>',
      edit: '<a href="?do=edit&path=' + encodeURIComponent(obj.real_path) + '" target="_blank" title="Edit">View / Edit</a>',
      copy: '<a class="copy" title="Copy">Copy</a>',
      move: '<a class="move" title="Move">Move</a>',
      rename: '<a class="rename" title="Rename">Rename</a>',
      delete: '<a class="delete" title="Delete">Delete</a>',
      cmprss: '<a class="cmprss" title="Compress">Compress</a>',
      extrct: '<a class="extrct" title="Extract">Extract</a>',
      permit: '<a class="permit" title="Permissions">Permissions</a>',
      info: '<a class="info" title="Info">View Details</a>',
    }
    if (!is_selected) {
      opt += obj.is_dir == 'true' ? menu.open : menu.runit;
      opt += obj.is_dir == 'true' ? '' : menu.dwnld;
      opt += obj.is_editable == 'true' ? menu.edit : '';
    }
    opt += ( obj.is_recursable == 'true' && obj.is_dir != 'true' )? menu.copy : '';
    opt += ( obj.is_recursable == 'true' && obj.is_dir != 'true' ) ? menu.move : '';
    opt += obj.is_deletable == 'true' ? menu.delete : '';
    if (!is_selected) {
      opt += ( obj.is_writable == 'true' && obj.is_dir != 'true' ) ? menu.rename : '';
      opt += obj.is_zipable == 'true' ? menu.cmprss : '';
      opt += obj.is_zip == 'true' ? menu.extrct : '';
      // opt += obj.is_writable == 'true' ? menu.permit : '';
      opt += menu.info;
    }

    if (e.ctrlKey || e.metaKey || e.shiftKey) {
      $item.addClass('selected');
    }

    if ($item.hasClass('selected') && $('main .selected').length > 1) {
      show_option_menu(e, opt);
    } else {
      $('main .item').removeClass('selected');
      show_option_menu(e, opt);
    }
  }


  /* LISTINGS
   *******************************************/
  function list() {
    $('body').addClass('loading');
    $('.toast.error').css('opacity', 0);
    var HASHVAL = decodeURIComponent(window.location.hash.substr(1));
    $.post('', {
      do: 'list',
      path: HASHVAL,
      xsrf: XSRF
    }, function (data) {
      $list.empty();
      $('#breadcrumb').empty().html(renderBreadcrumbs(HASHVAL)).animate({
        scrollLeft: '+=5000'
      });
      if (data.flag == true && Array.isArray(data.response)) {
        $('main').hasClass('listView') && $list.html('<div class="item tHead"><span class="tH name">Name</span><span class="tH size">Size</span><span class="tH time">Modified</span></div>');
        $.each(data.response, function (index, value) {
          $list.append(renderList(value));
        });
      } else {
        console.warn(data.response);
        toast(data.response, '', 'error');
      }

      $list.autoSort();
      $('body').removeClass('loading');
    }, 'json');
  }

  function renderList(data) {
    var dataAttr = {};
    $.each(data, function (key, value) {
      dataAttr['data-' + key] = value;
    });

    var fileSize = data.is_dir ? 'Folder' : formatFileSize(data.size);
    $link = $('<a>')
      .addClass(data.is_dir ? 'is_dir' : 'is_file')
      .attr('href', data.is_dir ? '#' + data.path : data.path)
      .attr('target', data.is_dir ? '_self' : '_blank')
      .attr('title', data.name)
      .attr(dataAttr);

    if ($('main').hasClass('listView')) {
      $link
        .append($('<span>').addClass('tD name').attr('data-sort', data.sort).attr('title', data.name).text(data.name).prepend($(svgIcons(data.ext)).addClass('icon')))
        .append($('<span>').addClass('tD size').attr('data-sort', data.size).attr('title', fileSize).text(fileSize))
        .append($('<span>').addClass('tD time').attr('data-sort', data.mtime).attr('title', timedate(data.mtime)).text(time_ago(data.mtime)))
        .append('<svg class="more" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>');
    } else {
      $link
        .append($(svgIcons(data.ext)).addClass('icon'))
        .append($('<span>').attr('rel', fileSize).text(data.name))
        .append('<svg class="more" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>');
    }
    return $('<div class="item">').html($link);
  }

  function renderBreadcrumbs(path) {
    var base = '',
      crumb = '<a href="#"><svg id="Capa_1" enable-background="new 0 0 511.925 511.925" height="512" viewBox="0 0 511.925 511.925" width="512" xmlns="http://www.w3.org/2000/svg"><g id="_x30_8_home"><path d="m430.772 276.2v191.784c0 8.324-6.771 15.096-15.096 15.096h-11.63c-4.143 0-7.5 3.357-7.5 7.5s3.357 7.5 7.5 7.5h11.63c16.595 0 30.096-13.501 30.096-30.096v-176.17l15.266 15.891c6.894 7.176 18.337 7.298 25.381.254l20.338-20.339c6.792-6.79 6.904-17.95.25-24.877l-230.822-240.279c-11.035-11.487-29.402-11.497-40.445 0l-230.823 240.279c-6.653 6.926-6.541 18.086.25 24.877l20.339 20.339c7.038 7.036 18.479 6.93 25.382-.254l15.264-15.889v36.432c0 4.143 3.358 7.5 7.5 7.5s7.5-3.357 7.5-7.5v-52.047l169.108-176.038c3.11-3.237 8.282-3.246 11.404 0 134.776 140.297 108.094 112.523 169.108 176.037zm65.378.812-20.339 20.34c-1.33 1.095-2.649 1.082-3.957-.04l-199.372-207.539c-.001-.001-.001-.001-.002-.002-.01-.011-.021-.02-.031-.031-9.023-9.358-23.99-9.354-33.006.032l-171.2 178.213c-.004.004-.007.009-.011.013l-28.161 29.314c-1.308 1.122-2.628 1.135-3.958.039l-20.339-20.339c-1.059-1.059-1.076-2.799-.039-3.878l230.822-240.279c5.139-5.349 13.674-5.348 18.811.001l230.821 240.277c1.038 1.08 1.021 2.821-.039 3.879z"/><path d="m368.897 483.079h-44.493v-84.919c0-37.738-30.703-68.44-68.442-68.44-37.738 0-68.441 30.702-68.441 68.44v84.919h-91.273c-8.324 0-15.096-6.771-15.096-15.096v-104.736c0-4.143-3.358-7.5-7.5-7.5s-7.5 3.357-7.5 7.5v104.736c0 16.595 13.501 30.096 30.096 30.096h272.649c4.143 0 7.5-3.357 7.5-7.5s-3.357-7.5-7.5-7.5zm-166.376-84.918c0-29.467 23.974-53.44 53.441-53.44s53.441 23.974 53.441 53.44v84.919h-106.882z"/></g></svg></a>';
    $.each(path.split('/'), function (index, value) {
      if (value) {
        crumb = crumb + '<a href="#' + base + value + '">' + value + '</a>';
        base += value + '/'
      }
    });
    return crumb;
  }
})(jQuery);

function svgIcons(ext = '') {
  switch (ext) {
    case '---':
      return '<svg viewBox="0 0 24 24"><path fill="#FA4" d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
      break;

    case 'aac':
    case 'aif':
    case 'aiff':
    case 'flac':
    case 'm4a':
    case 'm4p':
    case 'mp3':
    case 'wav':
    case 'wma':
      return '<svg viewBox="0 0 24 24"><path fill="#08F" d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>';
      break;

    case 'ai':
    case 'eps':
    case 'gif':
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'ps':
    case 'psd':
    case 'svg':
    case 'tif':
    case 'tiff':
      return '<svg viewBox="0 0 24 24"><path fill="#080" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>';
      break;

    case '3gp':
    case 'avi':
    case 'flv':
    case 'm4u':
    case 'mkv':
    case 'mov':
    case 'mp4':
    case 'mpg':
    case 'mpeg':
    case 'vob':
    case 'webm':
    case 'wmv':
      return '<svg viewBox="0 0 24 24"><path fill="#E00" d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>';
      break;

    case 'sh':
    case 'c':
    case 'cfm':
    case 'cpp':
    case 'class':
    case 'java':
    case 'jsp':
    case 'asp':
    case 'aspx':
    case 'rb':
    case 'pl':
    case 'py':
    case 'sql':
    case 'php':
    case 'phps':
    case 'phpx':
    case 'htm':
    case 'html':
    case 'whtml':
    case 'xhtml':
    case 'mht':
    case 'js':
    case 'json':
    case 'css':
    case 'xml':
      return '<svg viewBox="0 0 24 24"><path fill="#E13" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>';
      break;

    case '7z':
    case 'gz':
    case 'gzip':
    case 'rar':
    case 'tar':
    case 'tgz':
    case 'zip':
      return '<svg viewBox="0 0 24 24"><path fill="#700" d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>';
      break;

    case 'csv':
    case 'doc':
    case 'docx':
    case 'xlr':
    case 'xls':
    case 'xlsx':
    case 'pdf':
    case 'pps':
    case 'ppt':
    case 'pptx':
    case 'rtf':
    case 'odt':
    case 'txt':
    case 'text':
    case 'log':
      return '<svg viewBox="0 0 24 24"><path fill="#789" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';
      break;

    default:
      return '<svg viewBox="0 0 24 24"><path fill="#DDD" d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"/></svg>';
  }
}

document.querySelector('.overlay').addEventListener('click', function (e) {
  try {
    if (document.querySelector('.modal.on').getAttribute('id') != 'progressModal') {
      modal('off');
    }
  } catch (err) {}
});

function modal(act, selector = null) {
  try {
    hide_option_menu();
    document.querySelector('.modal.on').classList.remove('on');
  } catch (err) {}

  if (act == 'on') {
    document.querySelector('body').classList.add('modal_on');
    document.querySelector(selector).classList.add('on');
    try {
      document.querySelector(selector + ' input').focus();
    } catch (err) {}
  }
  if (act == 'off') {
    document.querySelector('body').classList.remove('modal_on');
  }
}

function hide_option_menu(e = '', clear = true) {
  if (clear) {
    [...menu_options.attributes].forEach(function (attr) {
      if (attr.name.indexOf('data-') > -1) {
        menu_options.removeAttribute(attr.name)
      }
    });
  }

  menu_options.style.height = 0;
  menu_options.style.opacity = 0;
  menu_options.style.visibility = 'hidden';
}

function show_option_menu(e, html = '') {
  e.preventDefault();
  hide_option_menu(e, false);

  menu_options.innerHTML = html;
  menu_options.style.height = 'auto';

  var offsetWidth = menu_options.offsetWidth;
  var offsetHeight = menu_options.offsetHeight;

  menu_options.style.height = 0;

  var isOutsideX = document.body.scrollWidth < (e.clientX + offsetWidth);
  var isOutsideY = document.body.scrollHeight < (e.clientY + offsetHeight);
  var posX = isOutsideX ? e.clientX - offsetWidth : e.clientX;
  var posY = isOutsideY ? e.clientY - offsetHeight : e.clientY;

  menu_options.style.top = posY + 'px';
  menu_options.style.left = posX + 'px';
  menu_options.style.opacity = 1;
  menu_options.style.visibility = 'visible';
  menu_options.style.height = offsetHeight + 'px';
}


function toast(message, color = '', time = 5000) {
  var toasts = document.querySelectorAll('.toast');
  for (var i = 0; i < toasts.length; i++) {
    toasts[i].dismiss();
  }

  var toast = document.createElement('div');
  toast.className = 'toast';
  typeof time != 'number' && toast.classList.add(time);
  time == 'wait' && document.querySelector('body').classList.add('toast_on');
  toast.dismiss = function () {
    this.style.bottom = '-10rem';
    this.style.opacity = 0;
    document.querySelector('body').classList.remove('toast_on');
  };

  var text = document.createTextNode(message);
  toast.appendChild(text);

  document.body.appendChild(toast);
  getComputedStyle(toast).bottom;
  getComputedStyle(toast).opacity;
  toast.style.backgroundColor = color;
  toast.style.bottom = document.body.scrollWidth > 576 ? '2rem' : '3.6rem';
  toast.style.opacity = 1;

  if (typeof time == 'number') {
    setTimeout(function () {
      toast.dismiss();
    }, time);
  }

  toast.addEventListener('transitionend', function (event, elapsed) {
    if (event.propertyName === 'opacity' && this.style.opacity == 0) {
      this.parentElement.removeChild(this);
    }
  }.bind(toast));
}

function formatFilePerm(val, dir = false) {
  val = 0 | parseInt(val, 8);
  var perm = !!(dir) ? 'd' : '-';
  perm += !!(256 & val) ? 'r' : '-';
  perm += !!(128 & val) ? 'w' : '-';
  perm += !!(64 & val) ? 'x' : '-';
  perm += !!(32 & val) ? 'r' : '-';
  perm += !!(16 & val) ? 'w' : '-';
  perm += !!(8 & val) ? 'x' : '-';
  perm += !!(4 & val) ? 'r' : '-';
  perm += !!(2 & val) ? 'w' : '-';
  perm += !!(1 & val) ? 'x' : '-';
  return perm;
}

function formatFileSize(bytes, round = 2) {
  if (bytes < 0) return 'Too Large';
  var units, power, size;
  units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
  bytes = Math.max(0, bytes);
  power = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
  power = Math.min(power, (units.length - 1));
  bytes /= Math.pow(1024, power);
  return Number(bytes.toFixed(round)) + ' ' + units[power];
}

function timedate(time) {
  var date = new Date(parseInt(time) * 1000);
  return date.toString().replace(/(\s*)GMT(.*)/, '');
}

function time_ago(time) {
  if(!time) {
    return ''
  }
  time = Date.now() - (parseInt(time) * 1000);
  var periods = {
    'decade': 60 * 60 * 24 * 30 * 12 * 10,
    'year': 60 * 60 * 24 * 30 * 12,
    'month': 60 * 60 * 24 * 30,
    'week': 60 * 60 * 24 * 7,
    'day': 60 * 60 * 24,
    'hr': 60 * 60,
    'min': 60,
    'sec': 1,
  };

  for (var unit in periods) {
    var seconds = periods[unit] * 1000;
    if (time < seconds) {
      continue;
    }

    number = Math.floor(time / seconds);
    plural = (number > 1) ? 's ago' : ' ago';
    return number + ' ' + unit + plural;
  }
}

function setCookie(cname, cvalue, exdays = 1) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = "expires=" + d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + "; path=/";
}

function getCookie(cname) {
  var name = cname + '=';
  var ca = decodeURIComponent(document.cookie).split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return '';
}

function _GET(name, url) {
  if (!url) url = window.location.href;
  name = name.replace(/[\[\]]/g, "\\$&");
  var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
    results = regex.exec(url);
  if (!results) return null;
  if (!results[2]) return '';
  return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function copy(str) {
  var flag = false;
  try {
    var save = function (e) {
      e.clipboardData.setData('text/plain', str);
      e.preventDefault();
    }
    document.addEventListener('copy', save);
    document.execCommand('copy');
    document.removeEventListener('copy', save);
    flag = true;
  } catch (e) {
    console.warn('Sorry, Unable to Copy');
  }
  return flag;
}

function nonce() {
  return Math.random().toString(36).substring(2);
}