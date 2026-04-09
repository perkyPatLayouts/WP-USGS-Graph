/**
 * USGS Water Level Graph Block
 *
 * No build process required - uses vanilla JavaScript with WordPress APIs
 *
 * Note: Block is registered via block.json and PHP register_block_type().
 * This file only provides the edit component.
 */

(function() {
	'use strict';

	const { createElement: el, useState, useEffect } = wp.element;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, ColorPicker } = wp.components;
	const { __ } = wp.i18n;
	const apiFetch = wp.apiFetch;

	// Register the edit component with WordPress
	wp.blocks.registerBlockType('usgs-water-levels/water-level-graph', {
		edit: EditComponent,
		save: function() {
			return null; // Dynamic block - rendered server-side
		}
	});

	/**
	 * Edit component
	 */
	function EditComponent(props) {
		const { attributes, setAttributes } = props;
		const { graphId, chartType, width, lineColor, backgroundColor, axisColor, labelColor } = attributes;
		const blockProps = useBlockProps();

		const [graphs, setGraphs] = useState([]);
		const [loading, setLoading] = useState(true);
		const [error, setError] = useState(null);

		// Fetch available graphs on mount
		useEffect(function() {
			apiFetch({ path: '/usgs-water-levels/v1/graphs' })
				.then(function(response) {
					setGraphs(response);
					setLoading(false);
				})
				.catch(function(err) {
					setError(err.message);
					setLoading(false);
				});
		}, []);

		// Prepare graph options
		const graphOptions = [
			{ label: __('Select a graph', 'usgs-water-levels'), value: 0 }
		].concat(
			graphs.map(function(graph) {
				return {
					label: graph.title,
					value: graph.id
				};
			})
		);

		// Build inspector controls
		const inspectorControls = el(
			InspectorControls,
			{},
			// Graph Settings Panel
			el(
				PanelBody,
				{
					title: __('Graph Settings', 'usgs-water-levels'),
					initialOpen: true
				},
				el(SelectControl, {
					label: __('Select Graph', 'usgs-water-levels'),
					value: graphId,
					options: graphOptions,
					onChange: function(value) {
						setAttributes({ graphId: parseInt(value) });
					},
					help: __('Choose which USGS monitoring location to display.', 'usgs-water-levels')
				}),
				el(SelectControl, {
					label: __('Chart Type', 'usgs-water-levels'),
					value: chartType,
					options: [
						{ label: __('Line Chart', 'usgs-water-levels'), value: 'line' },
						{ label: __('Area Chart', 'usgs-water-levels'), value: 'area' },
						{ label: __('Bar Chart', 'usgs-water-levels'), value: 'bar' }
					],
					onChange: function(value) {
						setAttributes({ chartType: value });
					},
					help: __('Choose how to display the water level data.', 'usgs-water-levels')
				}),
				el(TextControl, {
					label: __('Width', 'usgs-water-levels'),
					value: width,
					onChange: function(value) {
						setAttributes({ width: value });
					},
					help: __('Set the graph width (e.g., 100%, 800px, 50vw, 20em)', 'usgs-water-levels')
				})
			),
			// Colors Panel
			el(
				PanelBody,
				{
					title: __('Colors', 'usgs-water-levels'),
					initialOpen: false
				},
				el(
					'div',
					{ style: { marginBottom: '16px' } },
					el('label', {
						style: { display: 'block', marginBottom: '8px', fontWeight: 600 }
					}, __('Line Color', 'usgs-water-levels')),
					el(ColorPicker, {
						color: lineColor,
						onChangeComplete: function(color) {
							setAttributes({ lineColor: color.hex });
						}
					})
				),
				el(
					'div',
					{ style: { marginBottom: '16px' } },
					el('label', {
						style: { display: 'block', marginBottom: '8px', fontWeight: 600 }
					}, __('Background Color', 'usgs-water-levels')),
					el(ColorPicker, {
						color: backgroundColor,
						onChangeComplete: function(color) {
							setAttributes({ backgroundColor: color.hex });
						}
					})
				),
				el(
					'div',
					{ style: { marginBottom: '16px' } },
					el('label', {
						style: { display: 'block', marginBottom: '8px', fontWeight: 600 }
					}, __('Axis Color', 'usgs-water-levels')),
					el(ColorPicker, {
						color: axisColor,
						onChangeComplete: function(color) {
							setAttributes({ axisColor: color.hex });
						}
					})
				),
				el(
					'div',
					{ style: { marginBottom: '16px' } },
					el('label', {
						style: { display: 'block', marginBottom: '8px', fontWeight: 600 }
					}, __('Label Color', 'usgs-water-levels')),
					el(ColorPicker, {
						color: labelColor,
						onChangeComplete: function(color) {
							setAttributes({ labelColor: color.hex });
						}
					})
				)
			)
		);

		// Build preview content
		let previewContent;

		if (loading) {
			previewContent = el('p', {}, __('Loading graphs...', 'usgs-water-levels'));
		} else if (error) {
			previewContent = el(
				'p',
				{ style: { color: '#dc3232' } },
				__('Error loading graphs: ', 'usgs-water-levels') + error
			);
		} else if (graphId === 0) {
			previewContent = el('p', {}, __('Please select a graph from the block settings.', 'usgs-water-levels'));
		} else {
			previewContent = el(
				'div',
				{},
				el('h3', { style: { marginTop: 0 } }, __('USGS Water Level Graph', 'usgs-water-levels')),
				el(
					'p',
					{ style: { fontSize: '14px', color: '#666' } },
					__('Graph ID:', 'usgs-water-levels') + ' ' + graphId,
					el('br', {}),
					__('Chart Type:', 'usgs-water-levels') + ' ' + chartType.charAt(0).toUpperCase() + chartType.slice(1),
					el('br', {}),
					__('Width:', 'usgs-water-levels') + ' ' + width
				),
				el(
					'div',
					{
						style: {
							height: '200px',
							backgroundColor: backgroundColor,
							border: '1px solid #ddd',
							borderRadius: '4px',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							marginTop: '12px'
						}
					},
					el('span', { style: { color: lineColor, fontSize: '48px' } }, '📊')
				),
				el(
					'p',
					{ style: { fontSize: '12px', color: '#999', marginTop: '8px' } },
					__('Preview only - actual graph will be displayed on the frontend', 'usgs-water-levels')
				)
			);
		}

		// Return the complete block editor UI
		return el(
			'div',
			blockProps,
			inspectorControls,
			el(
				'div',
				{
					className: 'usgs-water-levels-chart-preview',
					style: {
						padding: '20px',
						border: '2px dashed #ccc',
						borderRadius: '4px',
						backgroundColor: '#f9f9f9',
						textAlign: 'center'
					}
				},
				previewContent
			)
		);
	}
})();
