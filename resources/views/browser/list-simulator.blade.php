@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Interactive Browser Simulator</h1>
    <form id="htmlForm" method="POST" action="#">
        @csrf
        <div class="form-group">
            <textarea name="htmlContent" class="form-control" id="htmlInput" rows="10" placeholder="Paste your HTML content here" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Load HTML</button>
    </form>

    <div class="mt-4">
        <iframe id="browserFrame" width="100%" height="600px"></iframe>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('htmlForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const htmlContent = document.getElementById('htmlInput').value;
    loadHtmlInIframe(htmlContent);
});

function loadHtmlInIframe(htmlContent) {
    const iframe = document.getElementById('browserFrame');
    iframe.srcdoc = htmlContent;

    // Wait until the iframe content is loaded
    iframe.onload = function() {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
        const recurringSelectors = findRecurringSelectors(iframeDocument);
        console.log(recurringSelectors);
        addIframeInteractivity(iframeDocument, recurringSelectors);
    };
}

function addIframeInteractivity(iframeDocument, recurringSelectors) {
    // Highlight all matching elements on hover and interact only with recurring elements
    iframeDocument.body.addEventListener('mouseover', function(event) {
        if (isRecurringElement(event.target, recurringSelectors)) {
            const selector = getElementSelector(event.target);
            highlightElements(iframeDocument, selector, '2px dashed #06b6d4');
        }
    });

    iframeDocument.body.addEventListener('mouseout', function(event) {
        if (isRecurringElement(event.target, recurringSelectors)) {
            const selector = getElementSelector(event.target);
            highlightElements(iframeDocument, selector, '');
        }
    });

    // Get the common CSS selector on click for recurring elements
    iframeDocument.body.addEventListener('click', function(event) {
        if (isRecurringElement(event.target, recurringSelectors)) {
            event.preventDefault();
            event.stopPropagation();

            const selector = getElementSelector(event.target);
            if (recurringSelectors.has(selector)) {
                console.log('Common CSS Selector:', selector);
                alert('Common CSS Selector: ' + selector);
            } else {
                const detailedSelector = getDetailedCssSelector(event.target);
                console.log('CSS Selector:', detailedSelector);
                alert('CSS Selector: ' + detailedSelector);
            }
        }
    });
}

function findRecurringSelectors(document) {
    const elements = document.querySelectorAll('[id], [class], [data-testid]');
    const selectorCounts = {};
    const recurringSelectors = new Set();

    elements.forEach(element => {
        const selector = getElementSelector(element);
        if (selector) {
            if (selectorCounts[selector]) {
                selectorCounts[selector]++;
                recurringSelectors.add(selector);
            } else {
                selectorCounts[selector] = 1;
            }
        }
    });

    return recurringSelectors;
}

function getElementSelector(element) {
    if (element.id) {
        return `#${element.id}`;
    } else if (element.className && typeof element.className === 'string') {
        return `[class="${element.className}"]`;
    } else if (element.getAttribute('data-testid')) {
        return `[data-testid="${element.getAttribute('data-testid')}"]`;
    }
    return null;
}

function isRecurringElement(element, recurringSelectors) {
    const selector = getElementSelector(element);
    return selector && recurringSelectors.has(selector);
}

function highlightElements(document, selector, style) {
    const elements = document.querySelectorAll(selector);
    elements.forEach(element => {
        element.style.outline = style;
    });
    return elements;
}

function getDetailedCssSelector(element) {
    if (element.tagName.toLowerCase() === 'html') {
        return 'html';
    }

    const path = [];

    while (element.parentElement) {
        let selector = element.tagName.toLowerCase();
        if (element.id) {
            selector += `#${element.id}`;
            path.unshift(selector);
            break;
        } else if (element.className) {
            const className = element.className.trim().split(/\s+/).map(cls => cls.replace(/:/g, '\\:')).join('.');
            selector += `.${className}`;
        } else if (element.getAttribute('data-testid')) {
            selector += `[data-testid="${element.getAttribute('data-testid')}"]`;
        } else {
            let sibling = element;
            let nth = 1;

            // Count siblings before this element to determine nth-child
            while (sibling = sibling.previousElementSibling) {
                if (sibling.tagName.toLowerCase() === selector) nth++;
            }
            selector += `:nth-of-type(${nth})`;
        }
        path.unshift(selector);
        element = element.parentElement;
    }

    return path.join(' > ');
}

</script>
@endsection
