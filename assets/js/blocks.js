/**
 * Gutenberg Custom Blocks JavaScript
 */

(function() {
    const { registerBlockType } = wp.blocks;
    const { RichText, InspectorControls, MediaUpload } = wp.blockEditor;
    const { PanelBody, Button, TextControl } = wp.components;
    const { __ } = wp.i18n;

    // Example Custom Block: Highlight Box
    registerBlockType('mi-theme/highlight-box', {
        title: __('MI Highlight Box', 'mi-theme'),
        icon: 'star-filled',
        category: 'mi-blocks',
        attributes: {
            title: {
                type: 'string',
                default: '',
            },
            content: {
                type: 'string',
                default: '',
            },
            backgroundColor: {
                type: 'string',
                default: '#C41E3A',
            },
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return (
                <div className="mi-highlight-box" style={{ backgroundColor: attributes.backgroundColor }}>
                    <InspectorControls>
                        <PanelBody title={__('Ayarlar', 'mi-theme')}>
                            <TextControl
                                label={__('Arka Plan Rengi', 'mi-theme')}
                                value={attributes.backgroundColor}
                                onChange={(value) => setAttributes({ backgroundColor: value })}
                            />
                        </PanelBody>
                    </InspectorControls>
                    <TextControl
                        placeholder={__('Başlık', 'mi-theme')}
                        value={attributes.title}
                        onChange={(value) => setAttributes({ title: value })}
                        className="mi-highlight-title"
                    />
                    <RichText
                        tagName="p"
                        placeholder={__('İçerik', 'mi-theme')}
                        value={attributes.content}
                        onChange={(value) => setAttributes({ content: value })}
                    />
                </div>
            );
        },
        save: function(props) {
            const { attributes } = props;
            return (
                <div className="mi-highlight-box" style={{ backgroundColor: attributes.backgroundColor }}>
                    {attributes.title && (
                        <h3 className="mi-highlight-title">{attributes.title}</h3>
                    )}
                    {attributes.content && (
                        <div className="mi-highlight-content">
                            <RichText.Content value={attributes.content} />
                        </div>
                    )}
                </div>
            );
        },
    });

    // Example Custom Block: Call to Action
    registerBlockType('mi-theme/call-to-action', {
        title: __('MI Call to Action', 'mi-theme'),
        icon: 'megaphone',
        category: 'mi-blocks',
        attributes: {
            title: {
                type: 'string',
                default: '',
            },
            description: {
                type: 'string',
                default: '',
            },
            buttonText: {
                type: 'string',
                default: __('Tıklayın', 'mi-theme'),
            },
            buttonUrl: {
                type: 'string',
                default: '',
            },
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return (
                <div className="mi-cta-block">
                    <TextControl
                        label={__('Başlık', 'mi-theme')}
                        value={attributes.title}
                        onChange={(value) => setAttributes({ title: value })}
                    />
                    <TextControl
                        label={__('Açıklama', 'mi-theme')}
                        value={attributes.description}
                        onChange={(value) => setAttributes({ description: value })}
                    />
                    <TextControl
                        label={__('Buton Metni', 'mi-theme')}
                        value={attributes.buttonText}
                        onChange={(value) => setAttributes({ buttonText: value })}
                    />
                    <TextControl
                        label={__('Buton URL', 'mi-theme')}
                        value={attributes.buttonUrl}
                        onChange={(value) => setAttributes({ buttonUrl: value })}
                    />
                </div>
            );
        },
        save: function(props) {
            const { attributes } = props;
            return (
                <div className="mi-cta-block">
                    {attributes.title && <h2 className="cta-title">{attributes.title}</h2>}
                    {attributes.description && <p className="cta-description">{attributes.description}</p>}
                    {attributes.buttonUrl && (
                        <a href={attributes.buttonUrl} className="cta-button">
                            {attributes.buttonText}
                        </a>
                    )}
                </div>
            );
        },
    });
})();

