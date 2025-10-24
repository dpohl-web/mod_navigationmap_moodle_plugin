/* eslint-disable */
define(['jquery', 'mod_navigationmap/interact'], function ($, interact) {
	return {
		init: function (useJokes) {
			$(function () {
				// target elements with the "draggable" class
				interact('.navigationmap__hotspot').draggable({
					// enable inertial throwing
					inertia: true,
					// keep the element within the area of it's parent
					modifiers: [
						interact.modifiers.restrictRect({
							restriction: 'parent',
							endOnly: true,
						}),
					],
					// enable autoScroll
					autoScroll: true,

					listeners: {
						// call this function on every dragmove event
						move: dragMoveListener,

						// call this function on every dragend event
						end(event) {
							const target = event.target;
							const hotspotIndex = target.dataset.hotspotIndex;
							const isMap = target.dataset.ismap;
							const isMapValue = isMap === '1' ? 'room' : 'topic';
                            const clientWidth = target.offsetWidth;
                            const clientHeight = target.offsetHeight;
							const positionToParentY = $(target).position().top + (clientHeight / 2);
							const positionToParentX = $(target).position().left + (clientWidth / 2);
							const parentWidth = target.offsetParent.offsetWidth;
							const parentHeight = target.offsetParent.offsetHeight;
							const positionToParentYPercent = ((positionToParentY / parentHeight) * 100).toFixed(1);
							const positionToParentXPercent = ((positionToParentX / parentWidth) * 100).toFixed(1);
							$(`#id_${isMapValue}_hotspot_xvalue_${hotspotIndex}`).val(positionToParentXPercent);
							$(`#id_${isMapValue}_hotspot_yvalue_${hotspotIndex}`).val(positionToParentYPercent);
						},
					},
				});

				function dragMoveListener(event) {
					var target = event.target;
                    const halfClientWidth = target.offsetWidth / 2;
                    const halfClientHeight = target.offsetHeight / 2;
                    const datax = parseFloat(target.getAttribute('data-x'));
                    const datay = parseFloat(target.getAttribute('data-y'));
					// keep the dragged position in the data-x/data-y attributes
					var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
					var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

					// translate the element
					target.style.transform = 'translate(' + x + 'px, ' + y + 'px) translate3d(-50%, -50%, 0)';

					// update the posiion attributes
					target.setAttribute('data-x', x);
					target.setAttribute('data-y', y);
				}

				// this function is used later in the resizing and gesture demos
				window.dragMoveListener = dragMoveListener;

				$('#navigationmap__hotpot-button--reset').on('click', function(){
                    if (useJokes) {
                        $.get( 'https://api.chucknorris.io/jokes/random')
                        .done(function(data) {
                            console.log('Chuck Norris Fact:', data.value);
                        });
                    }

					$('.navigationmap__hotspotimage-wrapper .navigationmap__hotspot').each(function(){
						const index = this.dataset.hotspotIndex;
						const x = this.dataset.hotspotXvalue;
						const y = this.dataset.hotspotYvalue;
						const isMap = this.dataset.ismap;
						const isMapValue = isMap === '1' ? 'room' : 'topic';
						$(`#id_${isMapValue}_hotspot_xvalue_${index}`).val(x);
						$(`#id_${isMapValue}_hotspot_yvalue_${index}`).val(y);
						this.removeAttribute('data-x');
						this.removeAttribute('data-y');
						this.style.transition = 'all 0.3s ease-in-out';
						this.style.transform = 'translate(0px, 0px) translate3d(-50%, -50%, 0)';
						var element = this;
						setTimeout(function() {
							element.style.transition = null;
						}, 400);
					});
				});
			});
		},
	};
});
/* eslint-enable */
