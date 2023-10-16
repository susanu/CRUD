@php
	$column['value'] = $column['value'] ?? data_get($entry, $column['name']);

    if($column['value'] instanceof \Closure) {
        $column['value'] = $column['value']($entry);
    }

	// if this attribute isn't using attribute casting, decode it
	if (is_string($column['value'])) {
	    $column['value'] = json_decode($column['value'], true);
    }

    if($column['value'] instanceof \Illuminate\Support\Collection) {
        $column['value'] = $column['value']->all();
    } else {
        // always work with arrays in the html, so if it is an object, get an array back from it.
		if(is_object($column['value'])) {
			$column['value'] = (array)$column['value'];
		}

		// check if it is a multidimensional array, if not we turn $value into one
		if (is_array($column['value']) && !empty($column['value']) && !is_multidimensional_array($column['value'])) {
			$column['value'] = array($column['value']);
		}
    }

	/** @var \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud */
@endphp

@if (!empty($column['value']) && count($column['columns']))

	@php
		$originalModel = $crud->getModel();
		$crud->setModel($column['value'][0]::class);
		$columnConfigs = array_map(fn($item) => $crud->makeSureColumnHasNeededAttributes($item), $column['columns']);
		$crud->setModel(is_string($originalModel) ?: $originalModel::class);
	@endphp

	@includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')

	<table class="table table-bordered table-condensed table-striped m-b-0">
		<thead>
			<tr>
				@foreach($columnConfigs as $columnConfig)
					<th>{{ $columnConfig['label'] }}</th>
				@endforeach
			</tr>
		</thead>
		<tbody>
			@foreach ($column['value'] as $tableRow)
				<tr>
					@foreach($columnConfigs as $columnConfig)
						@php
							// create a list of paths to column blade views
							// including the configured view_namespaces
							$columnPaths = array_map(function($item) use ($columnConfig) {
								return $item.'.'.$columnConfig['type'];
							}, \Backpack\CRUD\ViewNamespaces::getFor('columns'));

							// but always fall back to the stock 'text' column
							// if a view doesn't exist
							if (!in_array('crud::columns.text', $columnPaths)) {
								$columnPaths[] = 'crud::columns.text';
							}
						@endphp
						<td>
							@includeFirst($columnPaths, ['entry' => $tableRow, 'column' => $columnConfig])
						</td>
					@endforeach
				</tr>
			@endforeach
		</tbody>
	</table>

	@includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
@else
	<span>{{ $column['default'] ?? '-' }}</span>
@endif
