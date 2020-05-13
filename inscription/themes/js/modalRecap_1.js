
/**
 * Plugin pour la creation de poup de recapitulation
 * 
 */
(function ($) {
	$.fn.modalRecap = function (options) {
		var element = null;
		/**
		 * instentiation du html
		 */
		var html = '';

		/**
		 * Instentiation des params
		 */
		var paramsUrl = '';

		/**
		 * Fonction pour transformer les params en urls
		 */
		function paramsToUrl() {
//	console.log(options.element.data('numero'));
//	options.params.each(function(e, i){
//		alert(e+'__'+i);
//	});
		}
		;
		/**
		 * Construnction des html
		 */
		function constructHtml() {

			//Formatages des params
			paramsToUrl();

			$.ajax({
				url: options.urlAjax, // on appelle le script JSON
				dataType: 'html', // on spécifie bien que le type de données est en HTML
				success: function (data) {
					html += '<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-hidden="true">';
					html += '<div class="modal-dialog modal-dialog-centered" role="document">';
					html += '<div class="modal-content">';
					html += '<div class="modal-header">';
					html += '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
					html += '<span aria-hidden="true">&times;</span>';
					html += '</button>';
					html += '<h4 class="modal-title">' + options.title + ' : <label id="reception-reference"></label></h4> ';
					html += '</div>';
					html += '<div class="modal-body" id="reception-content">';
					html += data
					html += '</div>';
					html += '<div class="modal-footer">';
					html += '<a href="' + options.urlPDF + '" class="btn link-export-pdf" target="_blank">Exporter PDF</a>';
					html += '<a href="' + options.urlCSV + '" class="btn link-export-csv">Exporter CSV</a>';
					html += '</div>';
					html += '</div>';
					html += '</div>';
					html += '</div>';
				}
			});
		}
		;

		/**
		 * Contrôle des évenements
		 */
		$(document).on('click', options.element, function (e) {
			console.log($.this);
			//Construction des html du modal
			constructHtml();

			$(".modal").remove();
			$("#recap").append(html);
			$('#modal').modal('show');
		});

		/**
		 * Gestion de la fermetur du modal
		 */
		$(document).on('click', '.close', function () {
			$(".modal").remove();
			$('.modal-backdrop').hide();
			return false;
		});

		/**
		 * Gestion de la corps du modal
		 */
		$(document).on('click', '.modal', function () {
			return false;
		});
	};
})(jQuery);