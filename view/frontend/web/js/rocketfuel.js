function rocketRun(id) {
    payload = getPayload(id);
    let iframe_container = document.getElementById('rocketfuel-iframe-container');
    let iframe = document.getElementById('rocketfuel-iframe');
    let extension = document.querySelector('#rocketfuel-extension');

    if (checkExtension()) {
        if (iframe_container) {
            iframe_container.remove();
        }
        rocketfuel.toggleExtension();
        rocketfuel.setCartData(JSON.parse(payload));
    } else {
        iframe.contentWindow.postMessage({
            type: 'rocketfuel_send_cart',
            data: JSON.parse(getPayload(id))
        }, '*');
        // Make the iframe draggable:
        dragElement(document.getElementById("rocketfuel-drag"), iframe);
    }
}

function getPayload(id) {
    let url = '/rest/V1/rocketfuel-get-payload/' + id;
    let payload;
    //Get payload for rocketfuel cart
    const request = new XMLHttpRequest();
    request.open('GET', url, false);
    request.addEventListener("readystatechange", () => {
        if (request.readyState === 4 && request.status === 200) {
            payload = JSON.parse(request.responseText)
        }
    });
    request.send();
    return payload;
}

window.addEventListener('message', (event) => {
    let iframe = document.getElementById('rocketfuel-iframe');

    if (event.data.type === 'rocketfuel_result_ok') {
        console.log('rocketfuel_result_ok');
    }
    console.log(event)
    if (iframe) {

        if (event.data.type === 'rocketfuel_change_height') {
            console.log('rocketfuel_change_height');
            iframe.style.height = event.data.data;
        }

        if (event.data.type === 'rocketfuel_new_height') {
            console.log('rocketfuel_new_height');
            if (!!iframe) {
                const windowHeight = window.innerHeight - 20;
                if (windowHeight < event.data.data) {
                    iframe.style.height = windowHeight + 'px';
                    iframe.contentWindow.postMessage({
                        type: 'rocketfuel_max_height',
                        data: windowHeight,
                    }, '*');
                } else {
                    iframe.style.height = event.data.data + 'px';
                }
            }
        }
        // for iframe
        if (event.data.type === 'rocketfuel_iframe_close') {
            console.log('rocketfuel_iframe_close');
            iframe.remove();
        }

        if (event.data.type === 'rocketfuel_get_cart') {
            console.log('rocketfuel_get_cart');
            iframe.contentWindow.postMessage({
                type: 'rocketfuel_send_cart',
                data: getPayload()
            }, '*');
        }
    }
});


function dragElement(elmnt, iframe) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    if (document.getElementById(elmnt.id + "header")) {
        // if present, the header is where you move the DIV from:
        document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
    } else {
        // otherwise, move the DIV from anywhere inside the DIV:
        elmnt.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        // get the mouse cursor position at startup:
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        // call a function whenever the cursor moves:
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        // calculate the new cursor position:
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        // set the element's new position:
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
        iframe.style.top = (iframe.offsetTop - pos2) + "px";
        iframe.style.left = (iframe.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        // stop moving when mouse button is released:
        document.onmouseup = null;
        document.onmousemove = null;
    }
}

function checkExtension() {
    try {
        return Object.keys(rocketfuel).includes("setCartData");
    } catch (e) {
        return false;
    }

}
