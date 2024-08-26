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
            addIframeInteractivity(iframe);
        };
    }

    function addIframeInteractivity(iframe) {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

        // Highlight element on hover and interact only with recurring elements
        iframeDocument.body.addEventListener('mouseover', function(event) {
            if (event.target.matches('.target-element')) {
                event.target.style.outline = '2px solid blue';
            }
        });

        iframeDocument.body.addEventListener('mouseout', function(event) {
            if (event.target.matches('.target-element')) {
                event.target.style.outline = '';
            }
        });

        // Get the detailed CSS selector on click for recurring elements
        iframeDocument.body.addEventListener('click', function(event) {
            if (event.target.matches('.target-element')) {
                event.preventDefault();
                event.stopPropagation();

                const selector = getDetailedCssSelector(event.target);
                console.log('CSS Selector:', selector);
                alert('CSS Selector: ' + selector);
            }
        });
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
