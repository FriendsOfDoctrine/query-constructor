import React, { Component } from 'react'
import { makeFullName } from '../util/helpers'

class FieldsetWhere extends Component {
  constructor(props) {
    super(props);

    this.handleAdd = this.handleAdd.bind(this);
    this.handleRemove = this.handleRemove.bind(this);
    this.makeFullName = makeFullName.bind(this);
  }

  handleAdd() {
    this.props.add();
  }

  handleRemove(i) {
    this.props.remove(i);
  }

  render() {
    const filterCollectionPrefix = this.makeFullName('reportFilters');
    const WhereItem = this.props.WhereItem;
    const whereItems = this.props.whereCollection.map((where, i) => {
      const filterPrefix = this.makeFullName(i, filterCollectionPrefix);
      return (
      <div key={where.key}>
        <a
          onClick={() => this.handleRemove(i)}
          role="button"
          title="Удалить"
          className="sf__form-field__remove text-danger pull-right"
        >
          <span className="glyphicon glyphicon-remove"></span>
        </a>
        <WhereItem {...where} index={this.props.index} indexWhere={i} prefix={filterPrefix} />
      </div>
      )
    });
    return (
      <div className="sql-constructor-fieldset-where">
        {this.props.canAddItem && (
        <label className="control-label">Условия</label>
        )}
        {whereItems}
        {this.props.canAddItem && (
        <div>
          <a
            onClick={this.handleAdd}
            role="button"
            className="btn btn-primary btn-xs">
            <span className="glyphicon glyphicon-filter"></span> Добавить условие
          </a>
        </div>
        )}
      </div>
    )
  }
}

export default FieldsetWhere;
