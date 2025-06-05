function sortThumbnails(order = 'asc') {
   const container = document.getElementById('thumbnails');
   const thumbnails = Array.from(container.querySelectorAll('.bg-white')); // match your actual card class!

   thumbnails.sort((a, b) => {
      const pageA = parseInt(a.querySelector('input').value);
      const pageB = parseInt(b.querySelector('input').value);
      return order === 'asc' ? pageA - pageB : pageB - pageA;
   });

   container.innerHTML = '';
   thumbnails.forEach(thumbnail => container.appendChild(thumbnail));
}