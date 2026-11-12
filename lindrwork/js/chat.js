document.addEventListener('DOMContentLoaded', () => {
  const box = document.getElementById('messages');
  if (box) box.scrollTop = box.scrollHeight;
});
