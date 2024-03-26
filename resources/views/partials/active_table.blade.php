<thead>
    <tr>
        <th>Email</th>
        <th>Name</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
@foreach ($activeUsers as $user)
    <tr id="active_user_row_{{ $user->user_id }}">
        <td>{{ $user->email }}</td>
        <td>{{ $user->name }}</td>
        <td>
            <button class="deactivate-btn" data-user-id="{{ $user->user_id }}">Deactivate</button>
        </td>
    </tr>
@endforeach
</tbody>

