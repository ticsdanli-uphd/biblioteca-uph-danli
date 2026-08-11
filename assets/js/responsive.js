
/* Biblioteca UPH - mejoras responsive globales */
document.addEventListener('DOMContentLoaded', function () {
  // Envuelve automáticamente tablas que todavía no estén dentro de .table-responsive.
  document.querySelectorAll('table').forEach(function(table){
    if (!table.closest('.table-responsive')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'table-responsive';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    }
  });

  // Evita que formularios Bootstrap largos se salgan de la pantalla.
  document.querySelectorAll('form.row').forEach(function(form){
    form.style.maxWidth = '100%';
  });
});
