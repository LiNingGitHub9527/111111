;(function (w, $) {

	var Preview = (function () {

		function Preview(config) {
			this.$container = config.$container || $(document)
			this.$normalLayouts = this.$container.find('.nocode-layout-normal')
			this.$popupLayouts = this.$container.find('.nocode-layout-popup')
			this.$immediatelyPopupLayouts = this.$container.find('.nocode-layout-popup[data-popup-type="0"]')
			this.$delayPopupLayouts = this.$container.find('.nocode-layout-popup[data-popup-type="1"]')
			this.$offsetPopupLayouts = this.$container.find('.nocode-layout-popup[data-popup-type="2"]')
			this.$didPopupKeys = []
			this.$popupQueueKeys = []
			this.$currentPopupElement = null

			this.$popupLayoutTriggers = this.$container.find('[nocode-special-type="popup-click"]')

			this.bindEvent()
		}

		function popupInit(element) {
			this.$currentPopupElement = element
			let currentModel = $('#nocode-main-container').data('device')
			let size = $(element).data('popup-size')
			if (size) {
				let currentSize = size[currentModel]
				$(element).find('.nocode-editor-popup-box')
						.css('min-width', currentSize.width + 'px')
						.css('width', currentSize.width + 'px')
						.css('height', currentSize.height + 'px')
			}
			$(element).show()
		}

		Preview.prototype.bindEvent = function () {
			var _self = this

			$(document).on('scroll', function (e) {
				const scrollTop = (e.srcElement ? e.srcElement.documentElement.scrollTop : false)
						|| window.pageYOffset
						|| (e.srcElement ? e.srcElement.body.scrollTop : 0)

				_self.$offsetPopupLayouts.each(function (index, element) {
					var layoutKey = $(element).data('layout-key')
					if (!_self.didPopup(layoutKey)) {
						var offset = $(element).data('popup-offset')
						if (scrollTop >= offset) {
							_self.addPopup(layoutKey)
							_self.addPopupQueue(element)
						}
					}
				})
			})

			this.$delayPopupLayouts.each(function (index, element) {
				var layoutKey = $(element).data('layout-key')
				if (!_self.didPopup(layoutKey)) {
					var delay = $(element).data('popup-delay')
					var time = delay * 1000
					_self.addPopup(layoutKey)
					setTimeout(() => {
						_self.addPopupQueue(element)
					}, time)
				}
			})

			this.$popupLayouts.each(function (index, element) {
				$(element).on('click', '.nocode-editor-popup-close', function () {
					$(element).hide()
					_self.$currentPopupElement = null
				})
			})

			$('.nocode-editor-mask').on('click', function () {
				$('.nocode-layout-popup').hide()
			});

			this.$immediatelyPopupLayouts.each(function (index, element) {
				var layoutKey = $(element).data('layout-key')
				if (!_self.didPopup(layoutKey)) {
					_self.addPopup(layoutKey)
					_self.addPopupQueue(element)
				}
			})

			this.$popupLayoutTriggers.each(function (index, element) {
				if (element.hasAttribute('nocode-popup-click-id')) {
					$(element).on('click', function () {
						let layoutKey = $(this).attr('nocode-popup-click-id')
						let popupElement = $('[data-layout-key="' + layoutKey + '"]')
						popupInit(popupElement)
					})
				}
			})

			setInterval(function () {
				_self.popupQueue()
			}, 200)
		}

		Preview.prototype.didPopup = function (key) {
			return this.$didPopupKeys.indexOf(key) > -1
		}

		Preview.prototype.addPopup = function (key) {
			this.$didPopupKeys.push(key)
		}

		Preview.prototype.addPopupQueue = function (element) {
			this.$popupQueueKeys.push(element)
		}

		Preview.prototype.popupQueue = function () {
			if (!this.$currentPopupElement && this.$popupQueueKeys.length > 0) {
				let element = this.$popupQueueKeys.shift()
				popupInit(element)
			}
		}

		return Preview
	})()

	$(function () {
		console.log('xxxxxxxxxxxxxxx')
		new Preview({
			$container: $(this)
		})
		if (!$('#nocode-main-container').data('search-link')) {
			return
		}
		$('a[nocode-special-type="search"]').attr('href', $('#nocode-main-container').data('search-link'))
		$.each($('[nocode-special-type="css"]'), function (i, v) {
			let style = $(this).data('special-value')
			$(v).attr('style', style)
		})
	})

})(window, jQuery)
