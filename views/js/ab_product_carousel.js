document.addEventListener('DOMContentLoaded', function() {

    const sliders = document.querySelectorAll('.tinySlider');

    sliders.forEach(function(slider) {
        var tnsSlider = tns({
            container: slider,
            items: 3,
            axis: "vertical",
            speed: 400,
            gutter: 20,
            nav: false,
            mouseDrag: true,
        });
    });

});