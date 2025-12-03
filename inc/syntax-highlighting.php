<?php
/**
 * Code Syntax Highlighting
 */

// Enqueue Prism.js for syntax highlighting
function mi_enqueue_syntax_highlighting() {
    wp_enqueue_style('prism-css', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css', array(), '1.29.0');
    wp_enqueue_script('prism-js', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js', array(), '1.29.0', true);
    wp_enqueue_script('prism-autoloader', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js', array('prism-js'), '1.29.0', true);
    
    // Add language support
    wp_add_inline_script('prism-js', '
        window.Prism = window.Prism || {};
        window.Prism.manual = true;
    ');
}
add_action('wp_enqueue_scripts', 'mi_enqueue_syntax_highlighting');

// Add syntax highlighting to code blocks
function mi_syntax_highlight_code_blocks($content) {
    // Wrap code blocks with Prism classes
    $content = preg_replace_callback(
        '/<pre[^>]*><code[^>]*class="language-([^"]+)"[^>]*>(.*?)<\/code><\/pre>/is',
        function($matches) {
            $language = $matches[1];
            $code = htmlspecialchars_decode($matches[2]);
            return '<pre class="line-numbers"><code class="language-' . esc_attr($language) . '">' . esc_html($code) . '</code></pre>';
        },
        $content
    );
    
    // Handle plain code blocks
    $content = preg_replace_callback(
        '/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
        function($matches) {
            $code = htmlspecialchars_decode($matches[1]);
            return '<pre class="line-numbers"><code class="language-none">' . esc_html($code) . '</code></pre>';
        },
        $content
    );
    
    return $content;
}
add_filter('the_content', 'mi_syntax_highlight_code_blocks', 99);

// Add copy button to code blocks
function mi_add_code_copy_button($content) {
    $content = preg_replace_callback(
        '/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
        function($matches) {
            $code = $matches[1];
            return '<div class="code-block-wrapper">
                <button class="code-copy-btn" data-code="' . esc_attr(htmlspecialchars_decode($code)) . '" title="Kodu Kopyala">
                    <span class="copy-icon">📋</span>
                    <span class="copy-text">Kopyala</span>
                </button>
                ' . $matches[0] . '
            </div>';
        },
        $content
    );
    
    return $content;
}
add_filter('the_content', 'mi_add_code_copy_button', 100);

// Copy button script
function mi_code_copy_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.code-copy-btn');
        
        copyButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                const textarea = document.createElement('textarea');
                textarea.value = code;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                // Update button text
                const originalText = this.querySelector('.copy-text').textContent;
                this.querySelector('.copy-text').textContent = 'Kopyalandı!';
                this.classList.add('copied');
                
                setTimeout(function() {
                    button.querySelector('.copy-text').textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'mi_code_copy_script');

