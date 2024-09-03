@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Interactive Browser Simulator</h1>
    
    <form id="fetchHtmlForm" method="POST" action="{{ route('browser.simulator.fetch.html') }}">
        @csrf
        <label for="url">Enter URL:</label>
        <input type="text" id="urlInput" name="url" required>
        <button type="submit" class="btn btn-primary">Fetch HTML</button>
    </form>

    <form id="htmlForm" method="POST" action="#">
        @csrf
        <div class="form-group mt-4">
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
    document.getElementById('fetchHtmlForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const url = document.getElementById('urlInput').value;
        fetchHtml(url);
    });

    function fetchHtml(url) {
        fetch('{{ route('browser.simulator.fetch.html') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ url: url })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('htmlInput').value = data.html;
            } else {
                alert('Failed to fetch HTML: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error fetching HTML: ' + error);
        });
    }

    document.getElementById('htmlForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const htmlContent = document.getElementById('htmlInput').value;
        loadHtmlInIframe(htmlContent);
    });

    function loadHtmlInIframe(htmlContent) {
        const iframe = document.getElementById('browserFrame');
        iframe.srcdoc = htmlContent;
        
        iframe.onload = function() {
            addIframeInteractivity(iframe);
        };
    }

    function addIframeInteractivity(iframe) {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

        iframeDocument.body.addEventListener('mouseover', function(event) {
            event.target.style.outline = '2px solid blue';
        });

        iframeDocument.body.addEventListener('mouseout', function(event) {
            event.target.style.outline = '';
        });

        iframeDocument.body.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const selector = getDetailedCssSelector(event.target);
            console.log('CSS Selector:', selector);
            alert('CSS Selector: ' + selector);
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
