#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
勤怠管理システムのER図を生成するスクリプト
"""

def create_table_row(parent_id, row_id, pk_value, name_value, y_pos, is_fk=False):
    """テーブルの行を作成"""
    fk_style = ";fontStyle=2" if is_fk else ""
    return f'''                <mxCell id="{row_id}" value="" style="shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=0;" parent="{parent_id}" vertex="1">
                    <mxGeometry y="{y_pos}" width="250" height="30" as="geometry"/>
                </mxCell>
                <mxCell id="{row_id}-pk" value="{pk_value}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;editable=1;overflow=hidden;" parent="{row_id}" vertex="1">
                    <mxGeometry width="30" height="30" as="geometry">
                        <mxRectangle width="30" height="30" as="alternateBounds"/>
                    </mxGeometry>
                </mxCell>
                <mxCell id="{row_id}-name" value="{name_value}" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;overflow=hidden{fk_style};" parent="{row_id}" vertex="1">
                    <mxGeometry x="30" width="220" height="30" as="geometry">
                        <mxRectangle width="220" height="30" as="alternateBounds"/>
                    </mxGeometry>
                </mxCell>'''

def create_table(table_name, table_id, x, y, width, height, rows, fill_color="#dae8fc", stroke_color="#6c8ebf"):
    """テーブルを作成"""
    header = f'''                <mxCell id="{table_id}" value="{table_name}" style="shape=table;startSize=30;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;fillColor={fill_color};strokeColor={stroke_color};" parent="1" vertex="1">
                    <mxGeometry x="{x}" y="{y}" width="{width}" height="{height}" as="geometry"/>
                </mxCell>
                <mxCell id="{table_id}-header" value="" style="shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=1;" parent="{table_id}" vertex="1">
                    <mxGeometry y="30" width="{width}" height="30" as="geometry"/>
                </mxCell>
                <mxCell id="{table_id}-header-pk" value="PK" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;fontStyle=1;overflow=hidden;" parent="{table_id}-header" vertex="1">
                    <mxGeometry width="30" height="30" as="geometry">
                        <mxRectangle width="30" height="30" as="alternateBounds"/>
                    </mxGeometry>
                </mxCell>
                <mxCell id="{table_id}-header-name" value="id" style="shape=partialRectangle;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;fontStyle=5;overflow=hidden;" parent="{table_id}-header" vertex="1">
                    <mxGeometry x="30" width="{width-30}" height="30" as="geometry">
                        <mxRectangle width="{width-30}" height="30" as="alternateBounds"/>
                    </mxGeometry>
                </mxCell>'''
    
    row_content = ""
    y_pos = 60
    for row in rows:
        row_content += create_table_row(table_id, f"{table_id}-row{row['id']}", row['pk'], row['name'], y_pos, row.get('fk', False))
        y_pos += 30
    
    return header + row_content

def create_relationship(rel_id, source_table, target_table, source_row, target_row, exit_x, exit_y, entry_x, entry_y, rel_type="many"):
    """リレーションシップを作成"""
    arrow_style = "ERzeroToMany" if rel_type == "many" else "ERmandOne"
    return f'''                <mxCell id="{rel_id}" value="" style="endArrow={arrow_style};startArrow=ERmandOne;dashed=1;rounded=0;html=1;exitX={exit_x};exitY={exit_y};exitDx=0;exitDy=0;entryX={entry_x};entryY={entry_y};entryDx=0;entryDy=0;strokeWidth=2;startFill=0;endFill=0;edgeStyle=orthogonalEdgeStyle;" parent="1" source="{source_table}-row{source_row}" target="{target_table}-row{target_row}" edge="1">
                    <mxGeometry width="100" height="100" relative="1" as="geometry"/>
                </mxCell>'''

# テーブル定義
tables = {
    'roles': {
        'x': 50, 'y': 50, 'width': 250, 'height': 150,
        'rows': [
            {'id': 2, 'pk': '', 'name': 'name'},
            {'id': 3, 'pk': '', 'name': 'created_at'},
            {'id': 4, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'users': {
        'x': 50, 'y': 250, 'width': 250, 'height': 330,
        'rows': [
            {'id': 2, 'pk': '', 'name': 'name'},
            {'id': 3, 'pk': 'UK', 'name': 'email'},
            {'id': 4, 'pk': '', 'name': 'email_verified_at'},
            {'id': 5, 'pk': '', 'name': 'password'},
            {'id': 6, 'pk': '', 'name': 'remember_token'},
            {'id': 7, 'pk': 'FK', 'name': 'role_id', 'fk': True},
            {'id': 8, 'pk': '', 'name': 'created_at'},
            {'id': 9, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'attendances': {
        'x': 400, 'y': 50, 'width': 250, 'height': 330,
        'rows': [
            {'id': 2, 'pk': 'FK', 'name': 'user_id', 'fk': True},
            {'id': 3, 'pk': '', 'name': 'work_date'},
            {'id': 4, 'pk': '', 'name': 'started_at'},
            {'id': 5, 'pk': '', 'name': 'ended_at'},
            {'id': 6, 'pk': '', 'name': 'status'},
            {'id': 7, 'pk': '', 'name': 'total_break_minutes'},
            {'id': 8, 'pk': '', 'name': 'total_work_minutes'},
            {'id': 9, 'pk': '', 'name': 'created_at'},
            {'id': 10, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'attendance_breaks': {
        'x': 750, 'y': 50, 'width': 250, 'height': 210,
        'rows': [
            {'id': 2, 'pk': 'FK', 'name': 'attendance_id', 'fk': True},
            {'id': 3, 'pk': '', 'name': 'break_start_at'},
            {'id': 4, 'pk': '', 'name': 'break_end_at'},
            {'id': 5, 'pk': '', 'name': 'created_at'},
            {'id': 6, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'attendance_requests': {
        'x': 400, 'y': 450, 'width': 250, 'height': 360,
        'rows': [
            {'id': 2, 'pk': 'FK', 'name': 'attendance_id', 'fk': True},
            {'id': 3, 'pk': 'FK', 'name': 'parent_request_id', 'fk': True},
            {'id': 4, 'pk': '', 'name': 'requested_started_at'},
            {'id': 5, 'pk': '', 'name': 'requested_ended_at'},
            {'id': 6, 'pk': '', 'name': 'reason'},
            {'id': 7, 'pk': 'FK', 'name': 'requested_by', 'fk': True},
            {'id': 8, 'pk': 'FK', 'name': 'approver_id', 'fk': True},
            {'id': 9, 'pk': '', 'name': 'status'},
            {'id': 10, 'pk': '', 'name': 'created_at'},
            {'id': 11, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'attendance_request_breaks': {
        'x': 750, 'y': 450, 'width': 250, 'height': 210,
        'rows': [
            {'id': 2, 'pk': 'FK', 'name': 'attendance_request_id', 'fk': True},
            {'id': 3, 'pk': '', 'name': 'break_start_at'},
            {'id': 4, 'pk': '', 'name': 'break_end_at'},
            {'id': 5, 'pk': '', 'name': 'created_at'},
            {'id': 6, 'pk': '', 'name': 'updated_at'},
        ]
    },
    'attendance_approvals': {
        'x': 400, 'y': 900, 'width': 250, 'height': 420,
        'rows': [
            {'id': 2, 'pk': 'FK', 'name': 'attendance_id', 'fk': True},
            {'id': 3, 'pk': 'FK', 'name': 'attendance_request_id', 'fk': True},
            {'id': 4, 'pk': 'FK', 'name': 'approved_by', 'fk': True},
            {'id': 5, 'pk': '', 'name': 'approved_at'},
            {'id': 6, 'pk': '', 'name': 'status'},
            {'id': 7, 'pk': '', 'name': 'approved_at'},
            {'id': 8, 'pk': '', 'name': 'final_started_at'},
            {'id': 9, 'pk': '', 'name': 'final_ended_at'},
            {'id': 10, 'pk': '', 'name': 'final_break_minutes'},
            {'id': 11, 'pk': '', 'name': 'final_work_minutes'},
            {'id': 12, 'pk': '', 'name': 'created_at'},
            {'id': 13, 'pk': '', 'name': 'updated_at'},
        ]
    },
}

# リレーションシップ定義
relationships = [
    {'id': 'rel-roles-users', 'source': 'roles', 'target': 'users', 'source_row': 2, 'target_row': 7, 'exit_x': 0.5, 'exit_y': 1, 'entry_x': 0, 'entry_y': 0.5, 'type': 'one'},
    {'id': 'rel-users-attendances', 'source': 'users', 'target': 'attendances', 'source_row': 2, 'target_row': 2, 'exit_x': 1, 'exit_y': 0.5, 'entry_x': 0, 'entry_y': 0.5, 'type': 'many'},
    {'id': 'rel-attendances-attendance_breaks', 'source': 'attendances', 'target': 'attendance_breaks', 'source_row': 2, 'target_row': 2, 'exit_x': 1, 'exit_y': 0.5, 'entry_x': 0, 'entry_y': 0.5, 'type': 'many'},
    {'id': 'rel-attendances-attendance_requests', 'source': 'attendances', 'target': 'attendance_requests', 'source_row': 2, 'target_row': 2, 'exit_x': 0.5, 'exit_y': 1, 'entry_x': 0.5, 'entry_y': 0, 'type': 'many'},
    {'id': 'rel-users-attendance_requests-requested', 'source': 'users', 'target': 'attendance_requests', 'source_row': 2, 'target_row': 7, 'exit_x': 1, 'exit_y': 0.75, 'entry_x': 0, 'entry_y': 0.5, 'type': 'many'},
    {'id': 'rel-users-attendance_requests-approver', 'source': 'users', 'target': 'attendance_requests', 'source_row': 2, 'target_row': 8, 'exit_x': 1, 'exit_y': 0.9, 'entry_x': 0, 'entry_y': 0.7, 'type': 'many'},
    {'id': 'rel-attendance_requests-attendance_request_breaks', 'source': 'attendance_requests', 'target': 'attendance_request_breaks', 'source_row': 2, 'target_row': 2, 'exit_x': 1, 'exit_y': 0.5, 'entry_x': 0, 'entry_y': 0.5, 'type': 'many'},
    {'id': 'rel-attendance_requests-self', 'source': 'attendance_requests', 'target': 'attendance_requests', 'source_row': 3, 'target_row': 2, 'exit_x': 0.25, 'exit_y': 0, 'entry_x': 0.25, 'entry_y': 1, 'type': 'many'},
    {'id': 'rel-attendances-attendance_approvals', 'source': 'attendances', 'target': 'attendance_approvals', 'source_row': 2, 'target_row': 2, 'exit_x': 0.75, 'exit_y': 1, 'entry_x': 0.5, 'entry_y': 0, 'type': 'many'},
    {'id': 'rel-attendance_requests-attendance_approvals', 'source': 'attendance_requests', 'target': 'attendance_approvals', 'source_row': 2, 'target_row': 3, 'exit_x': 0.5, 'exit_y': 1, 'entry_x': 0.5, 'entry_y': 0, 'type': 'one'},
    {'id': 'rel-users-attendance_approvals', 'source': 'users', 'target': 'attendance_approvals', 'source_row': 2, 'target_row': 4, 'exit_x': 1, 'exit_y': 1, 'entry_x': 0, 'entry_y': 0.5, 'type': 'many'},
]

# XML生成
xml_content = '''<mxfile host="65bd71144e">
    <diagram name="ER Diagram" id="er-diagram">
        <mxGraphModel dx="1400" dy="1400" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1200" pageHeight="1400" math="0" shadow="0">
            <root>
                <mxCell id="0"/>
                <mxCell id="1" parent="0"/>
'''

# テーブル生成
for table_id, table_info in tables.items():
    xml_content += create_table(table_id, table_id, table_info['x'], table_info['y'], 
                                table_info['width'], table_info['height'], table_info['rows'])

# リレーションシップ生成
for rel in relationships:
    xml_content += create_relationship(rel['id'], rel['source'], rel['target'], 
                                      rel['source_row'], rel['target_row'],
                                      rel['exit_x'], rel['exit_y'], rel['entry_x'], rel['entry_y'],
                                      rel['type'])

xml_content += '''            </root>
        </mxGraphModel>
    </diagram>
</mxfile>'''

# ファイルに書き込み
with open('database_er_diagram.drawio', 'w', encoding='utf-8') as f:
    f.write(xml_content)

print("ER図を生成しました: database_er_diagram.drawio")

if __name__ == '__main__':
    pass

