// Confirmation before destructive admin actions: <form data-confirm="message">.
document.addEventListener('submit', function (event) {
  var form = event.target;
  if (form && form.dataset && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});
