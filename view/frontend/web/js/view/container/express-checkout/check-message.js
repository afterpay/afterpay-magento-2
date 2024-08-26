window.addEventListener("load", (event) => {

    let url = window.location.href;
    
    if (url.includes('success')) {
        const handleMessages = () => {
            let messages = document.querySelectorAll(".message.success");

            messages?.forEach((message) => {
                let links = message.getElementsByTagName('a');
                
                if(links.length > 0) {
                    for (let link of links) {
                        if (link.href.includes("checkout/cart")) {
                            message.remove();
                            break;
                        }
                    }
                }else {
                    message.classList.add("show-message");
                }
            });
        };

        handleMessages();

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    handleMessages();
                }
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
});
