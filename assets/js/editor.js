var ajax = {};
ajax.xhr = function () {
  if (typeof XMLHttpRequest != 'undefined') {
    return new XMLHttpRequest();
  }
  var _xhr, _ver = ["MSXML2.XmlHttp.6.0", "MSXML2.XmlHttp.5.0", "MSXML2.XmlHttp.4.0", "MSXML2.XmlHttp.3.0", "MSXML2.XmlHttp.2.0", "Microsoft.XmlHttp"];
  for (var i in _ver) {
    try {
      _xhr = new ActiveXObject(_ver[i]);
      break;
    } catch (e) {}
  }
  return _xhr;
};

ajax.send = function (url, method, data, callback, async) {
  var xhr = ajax.xhr();
  xhr.open(method, url, typeof async =='boolean' ? async :true);
  xhr.onreadystatechange = function () {
    xhr.readyState == 4 && callback(xhr.responseText);
  }
  method == 'POST' && xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.send(data)
};

function submitFrom(e) {
  e.preventDefault();
  toast('Saving...', '', 'wait');
  var formData = [];
  formData.push('do=save');
  formData.push('xsrf=' + document.querySelector('#xsrf').value);
  formData.push('nonce=' + Math.random().toString(36).substring(2));
  formData.push('content=' + encodeURIComponent(document.querySelector('#codedit').value));

  ajax.send('', 'POST', formData.join('&'), function (data) {
    data = JSON.parse(data);
    toast(data.response, data.flag == true ? '#070' : '#B00');
  });
}

window.onbeforeunload = function () {
  return 'Sorry, changes might not saved.';
}

window.onload = function () {
  autosize('#codedit');
  var size = document.querySelector('[data-bytes]').getAttribute('data-bytes');
  document.querySelector('[data-bytes]').setAttribute('data-size', formatFileSize(size));
}

window.onresize = function () {
  autosize('#codedit');
}

document.querySelector('#editor').addEventListener('submit', submitFrom);
document.querySelector('#codedit').addEventListener('keydown', autosize);
document.querySelector('#codedit').addEventListener('paste', function () {
  setTimeout(function (e) {
    autosize('#codedit');
  }, 0);
});
document.addEventListener('keydown', function (e) {
  if ((e.ctrlKey || e.metaKey) && e.keyCode == 83) {
    submitFrom(e);
  }
});

function autosize(elm = null) {
  elm = typeof elm == 'string' ? document.querySelector(elm) : this;
  var scrollPos = document.documentElement.scrollTop;
  elm.style.cssText = 'height: auto; padding: 0;';
  elm.style.cssText = 'height: ' + elm.scrollHeight + 'px; overflow: hidden;';
  document.documentElement.scrollTop = scrollPos;
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