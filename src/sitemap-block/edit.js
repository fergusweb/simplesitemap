/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
//import { useBlockProps } from '@wordpress/block-editor';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
//import './styles.scss';

import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import ServerSideRender from '@wordpress/server-side-render';


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { postTypes, showHeadings } = attributes;

	// useSelect to retrieve all post types
	const allPostTypes = useSelect(
		(select) => select(coreStore).getPostTypes({ per_page: -1 }), []
	);
	// Options expects [{label: ..., value: ...}]
	var postTypeOptions = !Array.isArray(allPostTypes) ? allPostTypes : allPostTypes
		.filter(
			// Filter out internal WP post types eg: wp_block, wp_navigation, wp_template, wp_template_part..
			postType => postType.viewable == true)
		.map(
			// Format the options for display in the <SelectControl/>
			(postType) => ({
				label: postType.labels.singular_name,
				value: postType.slug, // the value saved as postType in attributes
			})
		);
	//console.log(postTypeOptions);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Sitemap Settings', 'sitemap')}>
					<ToggleControl
						checked={!!showHeadings}
						label={__('Show post-type headings?', 'sitemap')}
						onChange={() =>
							setAttributes({
								showHeadings: !showHeadings,
							})
						}
					/>
					<SelectControl
						multiple
						label="Select post types to include"
						value={postTypes || ''}
						onChange={(value) => {
							setAttributes({ postTypes: value })
						}}
						options={postTypeOptions}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				<ServerSideRender
					block="sitemap/sitemap-block"
					attributes={attributes}
				/>
			</div>
		</>
	);
}
