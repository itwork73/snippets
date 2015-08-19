var testApp = {
	'isAcc':function(inputConfig){
		
		var defaultConfig = {
			'speed':'300',
			'initAttr':'data-is-tabs',
			'initAttrType':'.',
			'onBeforeChange':false,
			'onAfterChange':false,

			// private config

			'opacityDuration':'150',
			'opacityDelay':'150',
			'wrapperClass':'.is-tabs-wrapper',
			'contentClass':'.is-tabs-tab',
			'navLinkClass':'.is-tabs-link'
		};
		var tabsConfig = $.extend({}, defaultConfig, inputConfig);
		var tabsInit = function($thisTabs){

			var debouncer = false;
			var playStringRaw = 'transition:height '+tabsConfig.speed+'ms 0s, opacity '+(parseInt(tabsConfig.speed)+parseInt(tabsConfig.opacityDuration))+'ms '+tabsConfig.opacityDuration+'ms;';
			var playStringCSS = '-webkit-'+playStringRaw+'-moz-'+playStringRaw+'-ms-'+playStringRaw+'-o-'+playStringRaw+playStringRaw;
			var containerBox = $thisTabs.attr(tabsConfig.initAttr) || false;
			
			var $navLinks = $thisTabs.find(tabsConfig.navLinkClass);
			var $containerBox = $(tabsConfig.initAttrType + containerBox);
			var $wrapperBox = $containerBox.find(tabsConfig.wrapperClass);
			var $contentBox = $containerBox.find(tabsConfig.contentClass);

			var cH = $wrapperBox.outerHeight();
			var lastIndex = -1;

			var tabsCore = {
				'addEvent':function(){
					$navLinks.on('click', function(e){
						e.preventDefault();
						var $this = $(this);
						if (debouncer || $this.hasClass('active')) { return false; } 
						debouncer = true;
						var thisIndex = $this.index();
						$navLinks.removeClass('active');
						$this.addClass('active');
						if (tabsConfig.onBeforeChange) {
							var cbReturn = tabsConfig.onBeforeChange({
								'oldIndex':lastIndex,
								'newIndex':thisIndex,
								'thisLink':$this,
								'thisContent':$contentBox.eq(thisIndex)
							});
							if (cbReturn===false) {
								debouncer = false;
								return false;
							}
						}
						$containerBox.attr('style','opacity:0;height:'+cH+'px;');
						tabsCore.changeTab(thisIndex);
					});
				},
				'changeTab':function(thisIndex){
					$contentBox.hide().eq(thisIndex).show();
					cH = $wrapperBox.outerHeight();
					$containerBox.attr('style','opacity:1;height:'+cH+'px;'+playStringCSS);
					setTimeout(function(){
						$containerBox.attr('style','height:auto;');
						if (tabsConfig.onAfterChange) {
							var cbReturn = tabsConfig.onAfterChange({
								'oldIndex':lastIndex,
								'newIndex':thisIndex,
								'thisLink':$navLinks.eq(thisIndex),
								'thisContent':$contentBox.eq(thisIndex)
							});
							if (cbReturn===false) {
								debouncer = false;
								return false;
							}
						}
						lastIndex = thisIndex;
						debouncer = false;
					}, parseInt(tabsConfig.speed)+25);
				}
			};

			// init
			tabsCore.addEvent();
			$navLinks.eq(0).addClass('active');
		};

		$('['+tabsConfig.initAttr+']').each(function(i,thisTabs){ tabsInit($(thisTabs)); });
	}
}