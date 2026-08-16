// productos-mvc.js
// Este archivo debe cargarse DESPUÉS de inyectar BASE_URL desde PHP en la vista
// Ejemplo en vista PHP: <script>const BASE_URL = '<?php echo BASE_URL; ?>';</script>

$(document).ready(function () {

  // ---------- Configuración de URL ----------
  const guardarUrl = BASE_URL + 'producto/guardar';

  // ---------- Inicializar DataTable ----------
  const tabla = $("#tablaProductos").DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 20, 50],
    order: [],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excelHtml5",
        text: '<i class="fas fa-file-excel"></i> Excel',
        className: "btn btn-success",
        title: "Reporte de Inventario",
        autoFilter: true,
      },
      {
        extend: "pdfHtml5",
        text: '<i class="fas fa-file-pdf"></i> PDF',
        className: "btn btn-danger",
        title: "Reporte de Inventario",
        orientation: "landscape",
        pageSize: "A4",
      },
    ],
  });

  // ---------- Helpers ----------
  function resetModalFields(modal) {
    const form = modal.find("form")[0];
    if (form) form.reset();
    modal.find("#id_producto").val("");
    // NOTA: No tocamos aquí campos de imagen (preview, url, file, eliminar, etc.)
    // porque el manejo de imagen está centralizado en el index (script de la vista).
  }

  // ---------- Abrir modal para agregar o editar ----------
  $("#productoModal").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const modal = $(this);

    resetModalFields(modal);
    modal.find(".modal-title").text("Agregar Nuevo Producto");

    if (button.hasClass("edit-btn")) {
      modal.find(".modal-title").text("Editar Producto");
      modal.find("#id_producto").val(button.data("id") || "");
      modal.find("#nombre").val(button.data("nombre") || "");
      modal.find("#descripcion").val(button.data("descripcion") || "");
      modal.find("#id_categoria").val(button.data("categoria") || "");
      modal.find("#id_almacen").val(button.data("almacen") || "");
      modal.find("#precio_venta").val(button.data("precioventa") || "");
      modal.find("#precio_compra").val(button.data("preciocompra") || "");
      modal.find("#stock").val(button.data("stock") || "");
      modal.find("#stock_minimo").val(button.data("stockminimo") || "");

      // NOTA: los campos y preview de imagen los inicializa el script en index.
      // Aquí sólo nos aseguramos de dejar otros campos cargados.
    } else {
      // Agregar nuevo producto: dejamos que el script en index prepare los campos de imagen.
    }
  });

  // ---------- Envío del formulario ----------
  $("#formProducto").on("submit", function (e) {
    e.preventDefault();

    const formElem = this;
    const btnGuardar = $("#guardarProductoBtn");

    // Validación cliente (campos obligatorios básicos)
    const nombreVal = $.trim($("#nombre").val());
    const precioVal = $("#precio_venta").val();
    const stockVal = $("#stock").val();

    if (!nombreVal || precioVal === "" || stockVal === "") {
      Swal.fire("Atención", "Complete los campos obligatorios: Nombre, Precio venta y Stock.", "warning");
      return;
    }

    // NOTA: Validaciones relacionadas con imagen (tamaño, ambos campos, etc.)
    // se realizan en el script del index. Aquí no validamos imagen para evitar duplicidad.

    const fd = new FormData(formElem);

    btnGuardar.prop("disabled", true)
              .data("original-text", btnGuardar.html())
              .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
      url: guardarUrl,
      type: "POST",
      data: fd,
      processData: false,
      contentType: false,
      headers: { "X-Requested-With": "XMLHttpRequest" },
      timeout: 30000,
      success: function (response) {
        let data;
        try {
          data = typeof response === "object" ? response : JSON.parse(response);
        } catch (err) {
          console.error("Respuesta no JSON:", response);
          Swal.fire("Error", "Respuesta inválida del servidor.", "error");
          return;
        }

        if (data.success) {
          Swal.fire("Éxito", data.message || "Producto guardado.", "success");
          $("#productoModal").modal("hide");
          setTimeout(() => location.reload(), 700);
        } else {
          Swal.fire("Error", data.message || "No se pudo guardar.", "error");
        }
      },
      error: function (xhr) {
        console.error("Error AJAX:", xhr.responseText);
        let msg = "Error de conexión";
        try {
          const parsed = JSON.parse(xhr.responseText);
          msg = parsed.message || msg;
        } catch (e) {}
        Swal.fire("Error", msg, "error");
      },
      complete: function () {
        btnGuardar.prop("disabled", false).html(btnGuardar.data("original-text") || "Guardar");
      }
    });
  });

  // ---------- Confirmar acciones (activar/desactivar) ----------
  $(document).on("click", ".action-btn-confirm", function (e) {
    e.preventDefault();
    const href = this.href;
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, continuar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = href;
      }
    });
  });

  // ---------- Foco en modal ----------
  $("#productoModal").on("shown.bs.modal", function () {
    $(this).find("input:visible:first").trigger("focus");
  });

  // ---------- Limpiar modal al cerrar ----------
  $("#productoModal").on("hidden.bs.modal", function () {
    resetModalFields($(this));
    setTimeout(() => {
      if (document.activeElement && document.activeElement.tagName === "BUTTON") {
        document.activeElement.blur();
      }
    }, 10);
  });

});
