function encodeForAjax(data) {
    if (data == null) return null;
    return Object.keys(data).map(function (k) {
      return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
    }).join('&');
}
  
function sendAjaxRequest(method, url, data, handler) {
    let request = new XMLHttpRequest();
  
    request.open(method, url, true);
    request.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.addEventListener('load', handler);
    request.send(encodeForAjax(data));
}

document.addEventListener('DOMContentLoaded', function () {

    function loadActive(page) {
      sendAjaxRequest('GET', `/get-active-users?page=${page}`, null, function () {
        if (this.status >= 200 && this.status < 400) {
          document.getElementById('activeUsersTable').innerHTML = this.responseText;
        } else {
          console.error('Failed to load events.');
        }
      });
    }
  
    document.getElementById('activeUsersTable').addEventListener('click', function (e) {
      if (e.target.tagName === 'A' && e.target.getAttribute('class')=== 'page-link'){
        e.preventDefault();
        let page = e.target.getAttribute('href').split('page=')[1];
        loadActive(page);
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function () {

    function loadInactive(page) {
      sendAjaxRequest('GET', `/get-inactive-users?page=${page}`, null, function () {
        if (this.status >= 200 && this.status < 400) {
          document.getElementById('inactiveUsersTable').innerHTML = this.responseText;
        } else {
          console.error('Failed to load events.');
        }
      });
    }
  
    document.getElementById('inactiveUsersTable').addEventListener('click', function (e) {
      if (e.target.tagName === 'A' && e.target.getAttribute('class')=== 'page-link'){
        e.preventDefault();
        let page = e.target.getAttribute('href').split('page=')[1];
        loadInactive(page);
      }
    });
  });