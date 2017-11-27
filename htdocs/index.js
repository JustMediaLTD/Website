var winWidth = $(window).width(),
    winHeight = $(window).height(),
    wrapper = $("#wrapper"),
    spinner = $("#fidget-spinner");

// resize it
wrapper.width(winWidth/2);
wrapper.height(winWidth/2);

// spin it
Draggable.create(spinner, {
    type: "rotation", 
    throwProps: true,
    minDuration: 5,
    maxDuration: 30,
    throwResistance: 100,
    overshootTolerance: 1
});