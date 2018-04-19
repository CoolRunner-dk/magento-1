CoolRunner = function() {
	this.onAjaxComplete = false,
	this.reloadInterval = false,
	this.orderGridPostUrl = false,
	this.orderPackageLabelDownloadUrl = false,	
	this.getOrderGridLabels = function() {
		if(this.orderGridPostUrl && this.orderPackageLabelDownloadUrl)
		{
			var self = this;
			order_ids = [];
			$$('#sales_order_grid_table input[name=order_ids]').each(function(v){
				order_ids.push(v.value);
				if(!$('coolrunner-create-'+v.value)) {
					var input = $$('#sales_order_grid_table input[name=order_ids][value='+v.value+']');
					if(input.length) {
						input = input[0];
						
						var coolrunner_container = new Element('span', {
							'class': 'coolrunner-container',
							'data-order_id': v.value,					
						});
						var coolrunner_create = new Element('span', {
							'id': 'coolrunner-create-'+v.value,
							'title': 'Create CoolRunner Shipping Label',
							'data-open': 'coolrunner-form',
							'data-order_id': v.value,							
						});
						coolrunner_create.innerHTML = '<a class="coolrunner-create" href="#" >Create CoolRunner PDF</a>';
						$(coolrunner_container).appendChild(coolrunner_create)
						$(input).up('tr').down('td:last').appendChild(coolrunner_container);

						self.openModalOnElementClick($('coolrunner-create-'+v.value));
					}
				}
			});
			if(this.order_id) {
				order_ids.push(this.order_id);
				if(!$('coolrunner-create-'+this.order_id)) {
					var shipping_method_box = $$('.icon-head.head-shipping-method');
					if(shipping_method_box.length) {
						shipping_method_box = shipping_method_box[0];

						var coolrunner_container = new Element('span', {
							'class': 'coolrunner-container',
							'data-order_id': this.order_id,					
						});
						
						var coolrunner_create = new Element('span', {
							'id': 'coolrunner-create-'+this.order_id,
							'title': 'Create CoolRunner Shipping Label',
							'data-open': 'coolrunner-form',
							'data-order_id': this.order_id,							
						});
						
						coolrunner_create.innerHTML = '<br /><a class="coolrunner-create" href="#" >Create CoolRunner PDF</a>';
						$(coolrunner_container).appendChild(coolrunner_create);
						$(shipping_method_box).up('.entry-edit').down('fieldset').appendChild(coolrunner_container);

						self.openModalOnElementClick($('coolrunner-create-'+this.order_id));
					}
				}
			};
			if(order_ids.length){
				new Ajax.Request(this.orderGridPostUrl, {
					method: 'get',
					parameters: {
						'order_ids[]':order_ids,
					},
					onCreate: function() {
						$('loading-mask').hide();
					},
					onLoading: function() {
						$('loading-mask').hide();
					},
					onLoaded: function() {
						$('loading-mask').hide();
					},
					onComplete: function(transport) {
						$('loading-mask').hide();
						if(transport.responseText.evalJSON())
						{
							transport.responseText.evalJSON().each(function(v){
								if(!$('coolrunner-pdf-'+v.pdf_id) && $('coolrunner-create-'+v.order_id)) {
									
									var coolrunner_pdf = new Element('span', {
										'id': 'coolrunner-pdf-'+v.pdf_id,
										'title': 'Download CoolRunner Shipping Label'
									});
									coolrunner_pdf.innerHTML = '<a class="coolrunner-pdf-download" href="'+coolrunner.orderPackageLabelDownloadUrl+'id/'+v.pdf_id+'" target="_blank">CoolRunner PDF</a>';
									$('coolrunner-create-'+v.order_id).up().appendChild(coolrunner_pdf);
								}
							});	
						}
					}
				});
			}
		}
	},
	this.getOrderInfo = function(order_id) {
		if(this.orderInfoPostUrl && order_id)
		{
			new Ajax.Request(this.orderInfoPostUrl, {
				method: 'get',
				parameters: {
					'order_id':order_id,
				},
				onCreate: function() {
					$('loading-mask').hide();
				},
				onLoading: function() {
					$('loading-mask').hide();
				},
				onLoaded: function() {
					$('loading-mask').hide();
				},
				onComplete: function(transport) {
					$('loading-mask').hide();

					if(transport.responseText.evalJSON())
					{
						console.log(transport.responseText.evalJSON());	
					}
				}
			});	
		}
	},
	this.setOrderGridPostUrl = function(url){
		this.orderGridPostUrl = url;
	},
	this.setOrderInfoPostUrl = function(url){
		this.orderInfoPostUrl = url;
	},
	this.start = function() {
		var self = this;
		self.modalEscClose();
		var iframe = document.createElement("iframe");
		iframe.setAttribute('id','coolrunnerPdfLabelDownloadFrame');
		iframe.setAttribute('style','display:none');
		document.body.appendChild(iframe);
		
		self.getOrderGridLabels();
		Ajax.Responders.register({
		    onComplete: function(request, transport) {
				if(request.url.indexOf('coolrunner') < 0 && request.url.indexOf('labelsForOrders')) {
					self.getOrderGridLabels();
				}
		    }
		});
		if($('coolrunnerPdfLabelDownloadLink')){
			var pdfDownloadLink = $('coolrunnerPdfLabelDownloadLink').href;
			$('coolrunnerPdfLabelDownloadFrame').setAttribute('src', pdfDownloadLink);
		}
	},
	this.modalEscClose = function() {
		Event.observe(window, 'keyup',function modalEscClose(event){
			if(event.keyCode == 27) {
				if($('coolrunner-popup-modal')) {
					$('coolrunner-popup-modal').remove();
				}
				if($('coolrunner-popup-modal-overlay')) {
					$('coolrunner-popup-modal-overlay').remove();
				}
			}
		});
	},
	this.openModalOnElementClick = function(element) {
		var self = this;
		Event.observe(element, 'click',function openModalOnElementClick(event){
			
			Event.stop(event);
			
			var modal = new Element('div', {'id':'coolrunner-popup-modal'});
			var close = new Element('span', {'id':'coolrunner-popup-modal-close'}).update('✕');
			var opencontent = $(this).readAttribute('data-open');
			if(opencontent.indexOf("http://") >= 0) {
				content = new Element('iframe', {'src':opencontent});
			} else {
				var content = $(opencontent).innerHTML;				
			}
			var overlay = new Element('div', {'id':'coolrunner-popup-modal-overlay'});
			
			modal.insert({
				top: content,
				bottom: close
			});
			
			$$('body')[0].insert({
				top: overlay
			});
			
			$$('body')[0].insert({
				top: modal
			});
			self.addModelEvents($(this).readAttribute('data-order_id'));
			
		});
	},
	this.addModelEvents = function(order_id) {
		Event.observe($('coolrunner-popup-modal-close'), 'click',function(e){
			$('coolrunner-popup-modal').remove();
			$('coolrunner-popup-modal-overlay').remove();
		});
		
		Event.observe($('coolrunner-popup-modal-overlay'), 'click',function(e){
			$('coolrunner-popup-modal').remove();
			$('coolrunner-popup-modal-overlay').remove();
		});
		
		$$("#coolrunner-popup-modal #order_id")[0].value = order_id;
		
		if($$("#coolrunner-popup-modal #package-size").length) {
			$$("#coolrunner-popup-modal #package-size").each(function(element){
				Event.observe(element, 'change',function(){
					var options = $$("#coolrunner-popup-modal #package-size option[value="+this.value+"]");
					
					$$("#coolrunner-popup-modal #weight")[0].value = $(options[0]).readAttribute("data-weight");
					$$("#coolrunner-popup-modal #height")[0].value = $(options[0]).readAttribute("data-height");
					$$("#coolrunner-popup-modal #length")[0].value = $(options[0]).readAttribute("data-length");
					$$("#coolrunner-popup-modal #width")[0].value = $(options[0]).readAttribute("data-width");										
				});
			});
		}
		
	}
}
