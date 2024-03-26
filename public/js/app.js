function addEventListeners() {
  let activeUsersSection = document.getElementById('active_users_section');
  if (activeUsersSection) {
    activeUsersSection.addEventListener('click', function (event) {
      if (event.target.classList.contains('deactivate-btn')) {
        let userId = event.target.getAttribute('data-user-id');
        deactivateUser(userId);
      }
    });
  }

  let inactiveUsersSection = document.getElementById('inactive_users_section');
  if (inactiveUsersSection) {
    inactiveUsersSection.addEventListener('click', function (event) {
      if (event.target.classList.contains('activate-btn')) {
        let userId = event.target.getAttribute('data-user-id');
        activateUser(userId);
      }
    });
  }

} 

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

function submitFormOnFileChange() {
  var fileInput = document.getElementById("file-input");

  if (fileInput) {
      fileInput.addEventListener("change", function() {
          document.getElementById("upload-form").submit();
      });
  }
}

function makeFileContainerClickable() {
  const fileInput = document.getElementById('file-input');
  const fileContainer = document.getElementById('file-container');

  fileContainer.addEventListener('click', function () {
      fileInput.click();
  });
}


function updateStockContent(formData, ticketTypeId) {
  document.getElementById('new_stock_' + ticketTypeId).innerHTML = formData['new_stock_' + ticketTypeId];
  document.getElementById('stock_display_' + ticketTypeId).innerHTML = 'Stock: ' + formData['new_stock_' + ticketTypeId];
  let newStock = document.getElementById('new_stock_' + ticketTypeId).value;
  if (newStock == 0) {
    const label = document.getElementById('label' + ticketTypeId);
    const input = document.getElementById('input' + ticketTypeId);

    label.style.display = 'none';
    input.style.display = 'none';
  }
  else {

    const label = document.getElementById('label' + ticketTypeId);
    const input = document.getElementById('input' + ticketTypeId);
    if (label) {
      label.style.display = 'flex';
      input.style.display = 'flex';
    } else {
      const container = document.querySelector('#ticket-type-' + ticketTypeId);
      const max = container.getAttribute('data-max');
      let original = container.innerHTML;
      const update = original + ` <label class="quant" id ="label${ticketTypeId}" for="quantity_${ticketTypeId}">Quantity:</label>
        <input class="quant" id ="input${ticketTypeId}" type="number" id="quantity_${ticketTypeId}" name="quantity[${ticketTypeId}]" min="0" max="${max}">
        `;
      container.innerHTML = update;
    }

  }
}

function moveUserToInactiveTable(userId) {
  let activeUserRow = document.getElementById('active_user_row_' + userId);

  if (activeUserRow) {
    let activateButton = activeUserRow.querySelector('.deactivate-btn');
    activeUserRow.parentNode.removeChild(activeUserRow);
    activeUserRow.id = 'inactive_user_row_' + userId;
    let inactiveUsersTable = document.getElementById('inactive_users_section').querySelector('tbody');

    inactiveUsersTable.appendChild(activeUserRow);

    activateButton.innerText = 'Activate';
    activateButton.classList.remove('deactivate-btn');
    activateButton.classList.add('activate-btn');
    activateButton.removeEventListener('click', deactivateUser);
    activateButton.addEventListener('click', function () {
      activateUser(userId);
    });
  } else {
    console.error('Active user row not found:', userId);
  }
}

function moveUserToActiveTable(userId) {
  let inactiveUserRow = document.getElementById('inactive_user_row_' + userId);

  if (inactiveUserRow) {
    let deactivateButton = inactiveUserRow.querySelector('.activate-btn');
    inactiveUserRow.parentNode.removeChild(inactiveUserRow);
    inactiveUserRow.id = 'active_user_row_' + userId;
    let activeUsersTable = document.getElementById('active_users_section').querySelector('tbody');

    activeUsersTable.appendChild(inactiveUserRow);

    deactivateButton.innerText = 'Deactivate';
    deactivateButton.classList.remove('activate-btn');
    deactivateButton.classList.add('deactivate-btn');
    deactivateButton.removeEventListener('click', activateUser);
    deactivateButton.addEventListener('click', function () {
      deactivateUser(userId);
    });
  } else {
    console.error('Inactive user row not found:', userId);
  }
}

function updateStock(ticketTypeId) {
  let newStock = document.getElementById('new_stock_' + ticketTypeId).value;

  let formData = {
    ['new_stock_' + ticketTypeId]: newStock
  };

  sendAjaxRequest('POST', '/update-ticket-stock/' + ticketTypeId, formData, function () {
    if (this.status === 200) {
      let response = JSON.parse(this.responseText);
      updateStockContent(response, ticketTypeId);
      displaySuccessMessage("You have updated your ticket stock successfully");
  
  
    } else {
      console.error('Error updating stock:', this.responseText);
    }
  });
  
  
}


function deactivateUser(userId) {
  let formData = { 'user_id': userId };

  sendAjaxRequest('PUT', '/deactivateUser/' + userId, formData, moveUserToInactiveTable(userId));

  updateInactiveUserCount();
  updateActiveUserCount();
  updateActiveEventCount();
  updateInactiveEventCount();
  updateEventCountByMonth();
  updateEventCountByDay();
  updateEventCountByYear();
}

function activateUser(userId) {
  let formData = { 'user_id': userId };

  sendAjaxRequest('PUT', '/activateUser/' + userId, formData, moveUserToActiveTable(userId));

  updateInactiveUserCount();
  updateActiveUserCount();
  updateActiveEventCount();
  updateInactiveEventCount();
  updateEventCountByMonth();
  updateEventCountByDay();
  updateEventCountByYear();
}

function updateActiveUserCount() {
  sendAjaxRequest('GET', '/getActiveUserCount', null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('activeUserCount').innerText = 'Total de usuários ativos: ' + count;
  });
}

function updateInactiveUserCount() {
  sendAjaxRequest('GET', '/getInactiveUserCount', null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('inactiveUserCount').innerText = 'Total de usuários inativos: ' + count;
  });
}

function updateActiveEventCount() {
  sendAjaxRequest('GET', '/getActiveEventCount', null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('activeEventCount').innerText = count;
  });
}

function updateInactiveEventCount() {
  sendAjaxRequest('GET', '/getInactiveEventCount', null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('inactiveEventCount').innerText = count;
  });
}

function updateEventPageContent(formData) {
  if (formData.edit_name.trim() === '') {
      displayDangerMessage("Event name cannot be empty");
      return false;
  }

  if (formData.edit_location.trim() === '') {
      displayDangerMessage("Event location cannot be empty");
      return false;
  }

  if (formData.edit_description.trim() === '') {
      displayDangerMessage("Event description cannot be empty");
      return false;
  }

  let currentDate = new Date();
  let startTimestamp = new Date(formData.edit_start_timestamp);
  let endTimestamp = new Date(formData.edit_end_timestamp);

  if (startTimestamp < currentDate) {
      displayDangerMessage("The start timestamp must be superior to the current date");
      return false;
  }

  if (endTimestamp <= currentDate) {
      displayDangerMessage("The end timestamp must be superior to the current date");
      return false;
  }

  if (startTimestamp >= endTimestamp) {
      displayDangerMessage("The start timestamp must be earlier than the end timestamp.");
      return false;
  }

  let formattedStartDate = startTimestamp.getHours() + ':' + ('0' + startTimestamp.getMinutes()).slice(-2) + ' ' +
                            startTimestamp.getDate() + '/' + (startTimestamp.getMonth() + 1);

  let formattedEndDate = endTimestamp.getHours() + ':' + ('0' + endTimestamp.getMinutes()).slice(-2) + ' ' +
                          endTimestamp.getDate() + '/' + (endTimestamp.getMonth() + 1);

  document.getElementById('event-name').innerHTML = formData.edit_name;
  document.getElementById('location').innerHTML = formData.edit_location;
  document.getElementById('description').innerHTML = formData.edit_description;
  document.getElementById('ticket_start_date').innerHTML = 'Start: ' + formattedStartDate;
  document.getElementById('ticket_end_date').innerHTML = 'End: ' + formattedEndDate;

  displaySuccessMessage("You have updated your event successfully");

  return true; 
}

function updateEvent(eventId) {
  let formData = {
      'edit_name': document.getElementById('edit_name').value,
      'edit_description': document.getElementById('edit_description').value,
      'edit_location': document.getElementById('edit_location').value,
      'edit_start_timestamp': document.getElementById('edit_start_timestamp').value,
      'edit_end_timestamp': document.getElementById('edit_end_timestamp').value
  };

  if (updateEventPageContent(formData)) {
      sendAjaxRequest('post', '../update-event/' + eventId, formData);
  }
}

  

function displaySuccessMessage(message) {
  let successDiv = document.createElement('div');
  successDiv.classList.add('alert', 'alert-dismissible', 'alert-success', 'fixed-top-right');
  successDiv.innerHTML = `
    <strong>Well done!</strong> ${message}
  `;

  document.body.appendChild(successDiv);

  setTimeout(function () {
    removeSuccessMessage();
  }, 3500);
}





function removeSuccessMessage() {
  
  let successDiv = document.querySelector('.alert-success');
  if (successDiv) {
    successDiv.remove();
  }
}

function displayDangerMessage(message) {
  let dangerDiv = document.createElement('div');
  dangerDiv.classList.add('alert', 'alert-dismissible', 'alert-danger', 'fixed-top-right');
  dangerDiv.innerHTML = `
    <strong>Attention!</strong> ${message}
  `;

  document.body.appendChild(dangerDiv);

  setTimeout(function () {
    removeDangerMessage();
  }, 3500);
}

function removeDangerMessage() {
  let dangerDiv = document.querySelector('.alert-danger');
  if (dangerDiv) {
    dangerDiv.remove();
  }
}


function updateProfilePageContent(formData) {
  document.getElementById('user-header-name').innerText = formData.edit_name;

}

function updateProfile() {

  let formData = {
    'edit_name': document.getElementById('edit_name').value,
    'edit_email': document.getElementById('edit_email').value,
    'edit_phone_number': document.getElementById('edit_phone_number').value,
  };

  if (!formData.edit_name) {
    displayDangerMessage("Name field cannot be empty.");
    return;
}

if (!formData.edit_email) {
  displayDangerMessage("Email field cannot be empty.");
  return;
}

if (!formData.edit_phone_number) {
  displayDangerMessage("Phone Number field cannot be empty.");
  return;
}

const phoneNumberRegex = /^[0-9]+$/;
if (!phoneNumberRegex.test(formData.edit_phone_number)) {
    displayDangerMessage("Phone Number must only contain numbers.");
    return;
}

sendAjaxRequest('post', '../update-profile', formData,function () {
  
    let response = JSON.parse(this.responseText);
    
    if(response.message == "The email address is already in use by another user."){
      displayDangerMessage("The email address is already in use by another user.");
    
    


  } else {
    updateProfilePageContent(formData);

    displaySuccessMessage("You have updated your profile successfully");
}});

  document.getElementById('update-profile-button').style.display = 'none';
  document.getElementById('edit-profile-button').style.display = 'block';
  document.getElementById('edit_name').disabled = true;
  document.getElementById('edit_email').disabled = true;
  document.getElementById('edit_phone_number').disabled = true;




}

function updateTicketPageContent(ticketType) {
  let ticketTypesContainer = document.getElementById('ticket-types-container');
  let newTicketType = document.createElement('article');
  newTicketType.className = 'ticket-type';
  newTicketType.innerHTML = `
      <h3>${ticketType.name}</h3>
      <p>Stock: ${ticketType.stock}</p>
      <p>Description: ${ticketType.description}</p>
      <p>Price: ${ticketType.price} €</p>
      <label for="quantity_${ticketType.ticket_type_id}">Quantity:</label>
      <input type="number" id="quantity_${ticketType.ticket_type_id}" name="quantity[${ticketType.ticket_type_id}]" min="0" max="${ticketType.person_buying_limit}">
      
      <!-- New Stock -->
      <p>New Stock:
      <input type="number" id="new_stock_${ticketType.ticket_type_id}" name="new_stock" value="${ticketType.stock}" required>
      </p>
      <button class="button-update-stock" onclick="updateStock(${ticketType.ticket_type_id})" form="purchaseForm">Update Stock</button>
  `;
  ticketTypesContainer.appendChild(newTicketType);

  document.getElementById('ticket_name').value = '';
  document.getElementById('ticket_stock').value = '';
  document.getElementById('ticket_description').value = '';
  document.getElementById('ticket_person_limit').value = '';
  document.getElementById('ticket_price').value = '';
  document.getElementById('ticket_start_timestamp').value = '';
  document.getElementById('ticket_end_timestamp').value = '';



}

async function createTicketType(event_id) {
  let ticketName = document.getElementById('ticket_name').value;
  let ticketStock = document.getElementById('ticket_stock').value;
  let ticketPersonLimit = document.getElementById('ticket_person_limit').value;
  let ticketPrice = document.getElementById('ticket_price').value || 0;
  let ticketStartTimestamp = document.getElementById('ticket_start_timestamp').value;
  let ticketEndTimestamp = document.getElementById('ticket_end_timestamp').value;

  if (!ticketName || !ticketStock || !ticketPersonLimit || !ticketStartTimestamp || !ticketEndTimestamp) {
    displayDangerMessage("All fields are mandatory.");
    return;
}

if (isNaN(ticketPrice)) {
    displayDangerMessage("The ticket price must be a number.");
    return;
}

let currentDate = new Date().toISOString().split('T')[0];
if (ticketStartTimestamp < currentDate) {
    displayDangerMessage("The ticket start date must be equal to or later than the current date.");
    return;
}

if (ticketEndTimestamp <= currentDate) {
    displayDangerMessage("The ticket end date must be later than the current date.");
    return;
}

if (ticketStartTimestamp.split('T')[0] === ticketEndTimestamp.split('T')[0] && ticketStartTimestamp.split('T')[1] >= ticketEndTimestamp.split('T')[1]) {
    displayDangerMessage("The ticket start time must be earlier than the end time on the same day.");
    return;
}

let eventStartTimestamp = document.getElementById('edit_start_timestamp').value;
let eventEndTimestamp = document.getElementById('edit_end_timestamp').value;

if (ticketStartTimestamp < eventStartTimestamp || ticketStartTimestamp >= eventEndTimestamp) {
    displayDangerMessage("The ticket start date must be greater than or equal to the event start date and less than the event end date.");
    return;
}

if ((ticketEndTimestamp && ticketEndTimestamp <= eventStartTimestamp) || (ticketEndTimestamp && ticketEndTimestamp > eventEndTimestamp)) {
    displayDangerMessage("The ticket end date must be greater than the event start date and less than or equal to the event end date.");
    return;
}


  let formData = {
    'ticket_name': ticketName,
    'ticket_stock': ticketStock,
    'ticket_description': document.getElementById('ticket_description').value,
    'ticket_person_limit': ticketPersonLimit,
    'ticket_price': ticketPrice,
    'ticket_start_timestamp': ticketStartTimestamp,
    'ticket_end_timestamp': ticketEndTimestamp,
  };

  sendAjaxRequest('post', `../create-ticket-type/${event_id}`, formData, createTypeHandler);

  
  

}


function createTypeHandler() {
  if (this.status == 200) {
    window.location.reload();
    displaySuccessMessage("You have created your ticket successfully");
  }
}

const activate = document.getElementById('activate-button');

if (activate) {
  activate.addEventListener('click', function () {
    const eventId = activate.getAttribute('data-id');
    if (activate.textContent == 'Activate Event') {
      sendAjaxRequest('post', '/activate-event/' + eventId, {}, eventHandler)

    } else {
      sendAjaxRequest('post', '/deactivate-event/' + eventId, {}, event2Handler)
    }
  })
}


function eventHandler() {
  if (this.status == 200) {
    activate.textContent = 'Deactivate Event'
    activate.classList.remove('active')
  }
}

function event2Handler() {
  if (this.status == 200) {
    activate.textContent = 'Activate Event'
    activate.classList.add('active')
  }
}

function toggleProfileButtons() {
  document.getElementById('edit-profile-button').style.display = 'none';
  document.getElementById('update-profile-button').style.display = 'block';

  document.getElementById('edit_name').disabled = false;
  document.getElementById('edit_email').disabled = false;
  document.getElementById('edit_phone_number').disabled = false;
}


function toggleNotifications() {
  const notificationContainer = document.getElementById('notification-container');
  const notificationsBody = document.getElementById('notifications-body');
  const bodyElement = document.body;

  if (notificationContainer.style.display === 'none') {
    notificationContainer.style.display = 'grid';

    let loading = false;

    notificationsBody.addEventListener('scroll', function () {
      if (notificationsBody.scrollHeight - notificationsBody.scrollTop <= notificationsBody.clientHeight + 10) {
        if (!loading) {
          loading = true;
          loadNotifications(notificationsBody, function () {
            loading = false;
          });
        }
      }
    });

    if (!notificationContainer.style.maxHeight) {
      notificationContainer.style.maxHeight = (window.innerHeight - 90) + 'px';
    }

    notificationContainer.style.maxHeight = '90%';
    loadNotifications(notificationsBody);
    notificationContainer.style.position = 'fixed';
    bodyElement.style.overflow = 'hidden';

  } else {
    notificationContainer.style.display = 'none';
    notificationContainer.style.position = 'relative';
    bodyElement.style.overflow = 'auto';

  }
}


function loadNotifications(notificationsBody, callback) {
  fetch(`/get-notifications`)
    .then(response => response.json())
    .then(data => {

      notificationsBody.innerHTML = '';

      if (data.notifications.length === 0) {
        const noNotificationsText = document.createElement('p');
        noNotificationsText.textContent = 'Não tens notificações';
        notificationsBody.appendChild(noNotificationsText);
      } else {
        data.notifications.forEach(notification => {
          if (notification.viewed === false) {
            const notificationElement = document.createElement('div');
            notificationElement.setAttribute('id', `notification-${notification.id}`);
            notificationElement.classList.add(`notification-${notification.id}`);

            const iconElement = document.createElement('i');
            iconElement.classList.add('fa-solid');
            iconElement.classList.add('fa-xmark');
            iconElement.addEventListener('click', function () {
              dismissNotification(notification.id);
            });

            notificationElement.appendChild(iconElement);

            const horizontalSpace = document.createElement('br');
            notificationElement.appendChild(horizontalSpace);
            notificationElement.appendChild(horizontalSpace);

            const anchorTag = document.createElement('a');
            anchorTag.classList.add('event-link');
            if (notification.notification_type === 'Event') {
              anchorTag.href = `/view-event/${notification.event_id}`;
              anchorTag.innerHTML = `The event <strong>${notification.event_name || 'Unknown Event'}</strong> had some changes made. Check them out! `;
            } else if (notification.notification_type === 'Comment') {
              anchorTag.href = `/view-event/${notification.event_id}`;
              anchorTag.innerHTML = `A comment was made in the event <strong>${notification.event_name || 'Unknown Event'}</strong>. `;
            } else if (notification.notification_type === 'Report') {
              anchorTag.href = `/admin`;
              anchorTag.innerHTML = `A report on a comment was made in the event <strong>${notification.event_name || 'Unknown Event'}</strong>. `;
            }

            notificationElement.appendChild(anchorTag);

            const horizontalLine = document.createElement('hr');
            notificationElement.appendChild(horizontalLine);

            notificationsBody.appendChild(notificationElement);
          }
        });
      }

      if (callback) {
        callback();
      }
    })
    .catch(error => console.error('Error fetching notifications:', error));
}


function dismissNotification(notificationId) {
  sendAjaxRequest('POST', `/dismiss-notification/${notificationId}`, null, function () {
    const notificationsContainer = document.getElementById('notificationsContainer');

    const notificationElement = document.getElementById(`notification-${notificationId}`);

    if (notificationElement && notificationElement.parentNode) {
      notificationElement.parentNode.removeChild(notificationElement);
    }
    updateNotificationCount();
  });
}

function updateNotificationCount() {
  sendAjaxRequest('POST', '/update-notifications', null, function(event) {
      if (event.target.status === 200) {
          const responseData = JSON.parse(event.target.responseText);
          const notificationCount = responseData.count;

          document.querySelector('.notification-count').textContent = notificationCount;
      }
  });
}

document.addEventListener('DOMContentLoaded', function () {
  var notificationIcon = document.querySelector('.notification-icon');
  
  if (notificationIcon) {
      updateNotificationCount();
  }
});




function showSection() {
  var sectionButtons = document.querySelectorAll('.btn-check');
  var eventSections = document.getElementsByClassName("event-section");

  if (!sectionButtons.length || !eventSections.length) {
    return;
  }

  for (var j = 0; j < eventSections.length ; j++) {
    eventSections[j].style.display = "none";
  }

  var initialSection = document.getElementById('ticket-types');

  if (initialSection) {
    initialSection.style.display = "grid"; 
  }


  sectionButtons.forEach(function (button) {
    button.addEventListener("click", function () {

      var sectionId = this.getAttribute("data-section-id");

      var currentSection = document.getElementById(sectionId);

      for (var j = 0; j < eventSections.length; j++) {
        eventSections[j].style.display = "none";
      }

      if (currentSection) {
        if(sectionId =='event-info' || sectionId == 'create-ticket-type'){
          currentSection.style.display = "grid";
        }
        else{
          currentSection.style.display = "grid";
        }
      } else {
      }

      sectionButtons.forEach(function (btn) {
        btn.parentElement.classList.remove("selected");
      });

      this.parentElement.classList.add("selected");
    });
  });
}

showSection();

function showAdminSection() {
  var sectionButtons = document.querySelectorAll('.btn-check');
  var eventSections = document.getElementsByClassName("admin-section");

  if (!sectionButtons.length || !eventSections.length) {
    return;
  }

  for (var j = 1; j < eventSections.length; j++) {
    eventSections[j].style.display = "none";
  }

  sectionButtons.forEach(function (button) {
    button.addEventListener("click", function () {

      var sectionId = this.getAttribute("data-section-id");

      var currentSection = document.getElementById(sectionId);

      for (var j = 0; j < eventSections.length; j++) {
        eventSections[j].style.display = "none";
      }

      if (sectionId === 'manage-users' && currentSection) {
        currentSection.style.display = "flex";
      } else if (currentSection) {
        currentSection.style.display = "grid";
      } else {
      }

      sectionButtons.forEach(function (btn) {
        btn.parentElement.classList.remove("selected");
      });

      this.parentElement.classList.add("selected");
    });
  });
}






showAdminSection();


document.addEventListener("DOMContentLoaded", function () {
  let totalFields = document.querySelectorAll(".form-field").length;

  function updateProgressBar() {
    let filledFields = Array.from(document.querySelectorAll(".form-field")).filter(function (field) {
      return field.value.trim() !== "";
    }).length;

    let progress = (filledFields / totalFields) * 100;
    document.querySelector("#progress-bar-container .progress-bar").style.width = progress + "%";
    document.querySelector("#progress-bar-container .progress-bar").setAttribute("aria-valuenow", progress);
  }

  document.querySelectorAll(".form-field").forEach(function (field) {
    field.addEventListener("input", updateProgressBar);
    field.addEventListener("change", updateProgressBar);
  });
});






function toggleCheckoutSection() {
  var checkoutSection = document.getElementById('checkout-section');
  var buyButton = document.getElementById('checkout-button');
  var showForm = document.getElementById('show-form')

  checkoutSection.style.display = 'block';
  buyButton.style.display = 'block';
  showForm.style.display = 'none';
}





function confirmDeleteComment() {
  const comment = event.target.closest(".comment");

  comment.querySelector('#confirmDeleteCommentForm').style.display = 'block';
 
}

function confirmAdminDeleteComment() {
  const comment = event.target.closest("tr");

  comment.querySelector('#confirmAdminDeleteCommentForm').style.display = 'block';
}


function hideDeleteCommentModal() {
  const comment = event.target.closest(".comment");
  

  comment.querySelector('#confirmDeleteCommentForm').style.display = 'none';
 
}


function hideAdminDeleteCommentModal() {
  const comment = event.target.closest("tr");
  

  comment.querySelector('#confirmAdminDeleteCommentForm').style.display = 'none';
 
}



function showEditCommentModal() {
  const comment = event.target.closest(".comment");

  comment.querySelector('#commentText').style.display = 'none';
  

  comment.querySelector('#editCommentForm').style.display = 'block';
 
}


function hideEditCommentModal() {
  const comment = event.target.closest(".comment");

  comment.querySelector('#commentText').style.display = 'block';
  

  comment.querySelector('#editCommentForm').style.display = 'none';
 
}

function unlikeComment(){
  const comment = event.target.closest(".comment");

  const commentID = comment.getAttribute('data-id');
  
  event.preventDefault();
  
  sendAjaxRequest('post', '/unlike-comment',{comment_id: commentID});
  
  let likes = comment.querySelector('.comment-likes').textContent;
  likes = parseInt(likes, 10);
  likes = likes - 1;

  comment.querySelector('.comment-likes').textContent = likes.toString();

event.target.outerHTML = '<i class="far fa-thumbs-up fa-regular" id="unliked" onclick="likeComment(event)"></i>';
  
}

function unlikeCommentHandler() {
  
}


function likeComment(){
  const comment = event.target.closest(".comment");

  const commentID = comment.getAttribute('data-id');
  
  event.preventDefault();
  
  sendAjaxRequest('post', '/like-comment',{comment_id: commentID});
  
  let likes = comment.querySelector('.comment-likes').textContent;
  likes = parseInt(likes, 10);
  likes = likes + 1;

  comment.querySelector('.comment-likes').textContent = likes.toString();

event.target.outerHTML = '<i class="fas fa-thumbs-up fa-solid" id="liked" onclick="unlikeComment(event)"></i>';
  

}



function deleteComment(){
   const commentID = event.target.closest('.comment').getAttribute('data-id');
  event.preventDefault();
  sendAjaxRequest('post', '/delete-comment', { comment_id: commentID }, deleteCommentHandler);
}
function deleteCommentHandler() {
  const response = JSON.parse(this.responseText);
  const message = response.message;

  if (message && message.comment_id) {
    const commentId = message.comment_id;
    
    
    const commentElement = document.querySelector(`.comment[data-id="${commentId}"]`);
    
    if (commentElement) {
      commentElement.remove();
    } else {
      console.error('Comment element not found in HTML:', commentId);
    }
  } else {
    console.error('Invalid response structure or missing comment_id.');
  }
}

function deleteReportComment(event, reportedComment) {
  event.preventDefault();
  console.log(reportedComment);
  sendAjaxRequest('post', '/delete-report/' + reportedComment, { report_id: reportedComment }, deleteAdminCommentHandler1);
}

function deleteAdminCommentHandler1() {
  const response = JSON.parse(this.responseText);
  const message = response.message;

  if (message && response.report_id) {
      const reportId = response.report_id;

      const reportElement = document.querySelector(`tr[data-report-id="${reportId}"]`);

      if (reportElement) {
        reportElement.remove();
      } else {
          console.error('Comment element not found in HTML:', reportId);
      }
  } else {
      console.error('Invalid response structure or missing comment_id.');
  }
}

function editComment(){
  const comment = event.target.closest(".comment");
  
  
  commentID = comment.getAttribute('data-id');
  commentText = comment.querySelector('#editedCommentText').value;
  
  event.preventDefault();
  sendAjaxRequest('post', '/edit-comment',{newCommentText: commentText,comment_id: commentID} , editCommentHandler);

};

function editCommentHandler() {
  if (this.status === 200) {
    const response = JSON.parse(this.responseText);
    const editedComment = response.message;

    if (editedComment && editedComment.comment_id && editedComment.text) {
      const commentElement = document.querySelector(`.comment[data-id="${editedComment.comment_id}"]`);

      if (commentElement) {
        const commentTextElement = commentElement.querySelector('.comment-text');
        if (commentTextElement) {
          commentTextElement.textContent = editedComment.text;

          const editCommentForm = commentElement.querySelector('#editCommentForm');
          const commentText = commentElement.querySelector('#commentText');

          if (editCommentForm && commentText) {
            editCommentForm.style.display = 'none';
            commentText.style.display = 'block';
          } else {
            console.error('editCommentForm or commentText element not found.');
          }
        }
      } else {
        console.error('Comment element not found.');
      }
    } else {
      console.error('Invalid response structure or missing comment ID or text.');
    }
  }
}


function addNewComment(){
  
  eventID = document.querySelector('#newCommentEventID').value;
  commentText = document.querySelector('#newCommentText').value;
  
  event.preventDefault();
  sendAjaxRequest('post', '/submit-comment',{newCommentText: commentText,event_id: eventID} , addNewCommentHandler);

};

function addNewCommentHandler() {
  if (this.status === 200) {
    const response = JSON.parse(this.responseText);
    const newComment = response.message;

    if (newComment && newComment.text && newComment.author && newComment.author.name) {
      const commentElement = document.createElement('div');
      commentElement.className = 'comment';
      commentElement.setAttribute('data-id', newComment.comment_id);

      const commentIconsContainer = document.createElement('div');
      commentIconsContainer.className = 'comment-icons-container';

      const commentAuthor = document.createElement('p');
      commentAuthor.className = 'comment-author';
      commentAuthor.textContent = newComment.author.name; 

      const iconsDiv = document.createElement('div');

      const photoAndName = document.createElement('div');
      photoAndName.className = "photo-and-name";


      const baseUrl = document.getElementById('app').getAttribute('data-base-url');

      const authorPicture = document.createElement('img');
      authorPicture.id = 'profile-image-comment';
      authorPicture.alt = 'Profile Image';
      if (newComment.profile_image === null) {
       
        authorPicture.src = baseUrl + '../media/default_user.jpg';
      } else {
        
        authorPicture.src = baseUrl + `../profile_image/${newComment.profile_image}`;
      }
      photoAndName.appendChild(authorPicture);
      photoAndName.appendChild(commentAuthor);



      commentIconsContainer.appendChild(photoAndName);

      const editIcon = document.createElement('i');
      editIcon.className = 'fa-solid fa-pen-to-square';
      editIcon.addEventListener('click', function () {
        const commentText = commentElement.querySelector('.comment-text');
        if (commentText) {
          commentText.style.display = 'none';
        }

        const editCommentForm = commentElement.querySelector('#editCommentForm');
        if (editCommentForm) {
          editCommentForm.style.display = 'block';
        }
      });
      iconsDiv.appendChild(editIcon);

      const deleteIcon = document.createElement('i');
      deleteIcon.className = 'fa-solid fa-trash-can';
      
      deleteIcon.addEventListener('click', function(){
       
        const deleteCommentForm = commentElement.querySelector('#confirmDeleteCommentForm');
        
        deleteCommentForm.style.display = 'block';
        
      }) ;


      iconsDiv.appendChild(deleteIcon);


      
      commentIconsContainer.appendChild(iconsDiv);

      const commentText = document.createElement('p');
      commentText.className = 'comment-text';
      commentText.id = 'commentText';
      commentText.textContent = newComment.text;
     
      const editCommentForm = document.createElement('form');
      editCommentForm.id = 'editCommentForm';
      editCommentForm.style.display = 'none';

      const deleteCommentForm = document.createElement('form');
      deleteCommentForm.id = 'confirmDeleteCommentForm';
      deleteCommentForm.style.display = 'none';

      const editedCommentText = document.createElement('textarea');
      editedCommentText.id = 'editedCommentText';
      editedCommentText.className = 'edit-comment-textbox';
      editedCommentText.rows = '3';
      editedCommentText.value = newComment.text;
      editedCommentText.required = true;

      const deleteCommentText = document.createElement('p');
      deleteCommentText.id = 'deleteCommentText';
      deleteCommentText.className = 'text-danger';
      deleteCommentText.textContent = 'Are you sure you want to delete your comment?';
      
      

      const deleteSubmitButton = document.createElement('button');
      deleteSubmitButton.className = 'btn btn-danger';
      deleteSubmitButton.textContent = 'Delete';
      deleteSubmitButton.addEventListener('click', function () {
        const comment = event.target.closest('.comment');
        const commentID = comment.getAttribute('data-id');
       event.preventDefault();
       sendAjaxRequest('post', '/delete-comment', { comment_id: commentID }, deleteCommentHandler);
      });

      const submitButton = document.createElement('button');
      submitButton.className = 'btn btn-primary';
      submitButton.textContent = 'Submit';
      submitButton.addEventListener('click', function () {
        const comment = event.target.closest('.comment');
        const commentID = comment.getAttribute('data-id');
        const editedCommentText = comment.querySelector('#editedCommentText').value;

        event.preventDefault();
        sendAjaxRequest('post', '/edit-comment', { newCommentText: editedCommentText, comment_id: commentID }, editCommentHandler);
      });
      const deleteCancelButton = document.createElement('button');
      deleteCancelButton.className = 'btn btn-primary';
      deleteCancelButton.textContent = 'Cancel';
      deleteCancelButton.type = 'button';
      deleteCancelButton.style = 'margin-left: 4px;'
      deleteCancelButton.addEventListener('click', function () {
        const comment = event.target.closest('.comment');
        
        comment.querySelector('#confirmDeleteCommentForm').style.display = 'none';
      });
      

      const cancelButton = document.createElement('button');
      cancelButton.className = 'btn btn-danger';
      cancelButton.textContent = 'Cancel';
      cancelButton.type = 'button';
      cancelButton.style = 'margin-left: 4px;'
      cancelButton.addEventListener('click', function () {
        const comment = event.target.closest('.comment');
        comment.querySelector('#commentText').style.display = 'block';
        comment.querySelector('#editCommentForm').style.display = 'none';
      });
      
      deleteCommentForm.appendChild(deleteCommentText);
      deleteCommentForm.appendChild(deleteSubmitButton);
      deleteCommentForm.appendChild(deleteCancelButton);
      
      editCommentForm.appendChild(editedCommentText);
      editCommentForm.appendChild(submitButton);
      editCommentForm.appendChild(cancelButton);
      
      const commentLikesSection = document.createElement('div');
      commentLikesSection.className = 'comment-likes-section';

      const commentLikes = document.createElement('p');
      commentLikes.className = 'comment-likes';
      commentLikes.textContent = '0'; 

      const likeIcon = document.createElement('i');
      likeIcon.className = 'far fa-thumbs-up fa-regular';
      likeIcon.addEventListener('click', likeComment);

      commentLikesSection.appendChild(likeIcon);
      commentLikesSection.appendChild(commentLikes);
      

      commentElement.appendChild(commentIconsContainer);
      commentElement.appendChild(commentText);
      commentElement.appendChild(editCommentForm);
      commentElement.appendChild(deleteCommentForm);
      commentElement.appendChild(commentLikesSection);

      const commentsContainer = document.querySelector('.commentsContainer');
      if (commentsContainer) {
        commentsContainer.prepend(commentElement);
      } else {
        console.error('Comments container not found.');
      }
     
      
      document.getElementById('newCommentText').value = '';
    } else {
      console.error('Invalid response structure or missing comment text or author ID.');
    }
  }
}






function showEditRatingForm() {
  document.getElementById('editRatingForm').style.display = 'block';
  document.getElementById('yourRatingP').style.display = 'none';
}


function redirectToLogin() {
  window.location.href = "/login";
}if (localStorage.getItem('resetSuccess') !== 'true') {
  function showSuccessAlert() {
      var alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-dismissible alert-success';
      alertDiv.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>The reset password has been sent to your email account!';

      document.body.appendChild(alertDiv);

      localStorage.setItem('resetSuccess', 'true');
  }

  showSuccessAlert();
}



function showReportPopUp(){
    const comment_id = event.target.closest('.comment').getAttribute('data-id');
   
  
  document.getElementById('reportCommentId').value = comment_id;

const reportPopUp = document.querySelector('.pop-up-report');
    reportPopUp.style.display = 'block';

    window.onclick = function(event) {
        if (event.target == reportPopUp) {
            reportPopUp.style.display = 'none';
        }
    };
  }




function updateEventCountByMonth() {
  let currentMonth = new Date().getMonth() + 1;

  sendAjaxRequest('GET', '/getEventCountByMonth/' + currentMonth, null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('eventCountByMonth').innerText = count;
  });
}

function updateEventCountByDay() {
  let currentDay = new Date().getDate();

  sendAjaxRequest('GET', '/getEventCountByDay/' + currentDay, null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('eventCountByDay').innerText = count;
  });
}

function updateEventCountByYear() {
  let currentYear = new Date().getFullYear();

  sendAjaxRequest('GET', '/getEventCountByYear/' + currentYear, null, function (event) {
    let count = JSON.parse(event.target.responseText).count;
    document.getElementById('eventCountByYear').innerText = count;
  });
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.see-all-buyed-row').forEach(function (row) {
      row.addEventListener('click', function () {
          let detailsSection = row.querySelector('.additional-info');
          detailsSection.style.display = detailsSection.style.display === 'none' ? 'table-row' : 'none';

          row.classList.toggle('row-open');
          let tbody = row.parentNode;
          tbody.classList.toggle('tbody-open');

      });
  });
});



document.addEventListener('DOMContentLoaded', function () {
  var allContainers = document.querySelectorAll('.my-tickets-container');

  allContainers.forEach(function (container) {
    var ticketsPerEvent = container.querySelector('.my-tickets-per-event');
    var seeMoreButton = container.querySelector('.my-tickets-btn-see-more');
    var hiddenButton = container.querySelector('.my-tickets-btn-hidden');

    if (ticketsPerEvent && seeMoreButton && hiddenButton) {
      seeMoreButton.addEventListener('click', function () {
        allContainers.forEach(function (otherContainer) {
          var otherTicketsPerEvent = otherContainer.querySelector('.my-tickets-per-event');
          var otherSeeMoreButton = otherContainer.querySelector('.my-tickets-btn-see-more');
          var otherHiddenButton = otherContainer.querySelector('.my-tickets-btn-hidden');

          if (otherTicketsPerEvent && otherSeeMoreButton && otherHiddenButton) {

                var hasOverflow = otherTicketsPerEvent.scrollHeight > otherTicketsPerEvent.clientHeight;

              if (hasOverflow) {
                otherTicketsPerEvent.style.maxHeight = '340px';
                otherTicketsPerEvent.style.overflow = 'scroll';
                otherSeeMoreButton.style.display = 'flex';
                otherHiddenButton.style.display = 'none';
              }
              else {
                seeMoreButton.style.display = 'none';
                hiddenButton.style.display = 'none';
              }
            
          }
        });

        ticketsPerEvent.style.maxHeight = 'fit-content';
        ticketsPerEvent.style.overflow = 'visible';
        seeMoreButton.style.display = 'none';
        hiddenButton.style.display = 'flex';
      });


      hiddenButton.addEventListener('click', function () {
        ticketsPerEvent.style.maxHeight = '340px';
        ticketsPerEvent.style.overflow = 'scroll';
        hiddenButton.style.display = 'none';
        seeMoreButton.style.display = 'flex';
      });

      var hasOverflow = ticketsPerEvent.scrollHeight > ticketsPerEvent.clientHeight;


      if (hasOverflow) {
        seeMoreButton.style.display = 'flex';
        hiddenButton.style.display = 'none';
      } else {
        seeMoreButton.style.display = 'none';
        hiddenButton.style.display = 'none';
      }
    }
  });
});

document.addEventListener('DOMContentLoaded', function () {
  var currentDate = new Date();
  var formattedDate = currentDate.toISOString().slice(0, 16);

  var ticketStartTimestampElement = document.getElementById('ticket_start_timestamp');

  if (ticketStartTimestampElement) {
      ticketStartTimestampElement.value = formattedDate;
  }

  var ticketEndTimestampElement = document.getElementById('ticket_end_timestamp');

  if (ticketEndTimestampElement) {
      ticketEndTimestampElement.value = formattedDate;
  }
});

document.addEventListener('DOMContentLoaded', function () {
  var currentDate = new Date();
  var formattedDate = currentDate.toISOString().slice(0, 16);

  var ticketStartTimestampElement = document.getElementById('ticket_start_timestamp');
  var ticketEndTimestampElement = document.getElementById('ticket_end_timestamp');

  if (ticketStartTimestampElement) {
      ticketStartTimestampElement.value = formattedDate;
  }

  if (ticketEndTimestampElement) {
      currentDate.setDate(currentDate.getDate() + 1);
      formattedDate = currentDate.toISOString().slice(0, 16);
      ticketEndTimestampElement.value = formattedDate;
  }
});




    document.querySelectorAll('.numeric-input input[type="number"]').forEach(function (input) {
      input.addEventListener('input', function () {
          this.value = this.value.replace(/[^0-9]/g, ''); 
      });

      input.addEventListener('change', function () {
          if (parseInt(this.value) < 0) {
              this.value = 0;
          }
      });
  });

  document.querySelectorAll('.numeric-input .btn-increment').forEach(function (btn) {
      btn.addEventListener('click', function () {
          var input = this.parentNode.querySelector('input[type="number"]');
          input.stepUp();
      });
  });

  document.querySelectorAll('.numeric-input .btn-decrement').forEach(function (btn) {
      btn.addEventListener('click', function () {
          var input = this.parentNode.querySelector('input[type="number"]');
          if (parseInt(input.value) > 0) {
              input.stepDown();
          }
      });
      
  });


  addEventListeners();

  submitFormOnFileChange();

  