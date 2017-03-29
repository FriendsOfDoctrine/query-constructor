import React, { Component } from 'react'
import { connect } from 'react-redux'
import { mapStateToQuery } from '../redux/modules/reducer'
import FieldsetSelect from './SqlConstructor/FieldsetSelect'
import FieldsetWhere from './SqlConstructor/FieldsetWhere'
import { bindAllTo, makeFullName } from '../util/helpers'

class SqlContructor extends Component {

  constructor(props) {
    super(props);

    this.state = {isVisible: false};
    this.handleToggleVisibility = this.handleToggleVisibility.bind(this);
    this.makeFullName = makeFullName.bind(this);
  }

  handleToggleVisibility() {
    this.setState({ isVisible: !this.state.isVisible });
  }

  render() {
    return this.state.isVisible
      ? (
        <div className="sql-constructor">
          <div className="sql-constructor__fieldset-select">
            <FieldsetSelect prefix={this.props.prefix}/>
          </div>
          <div className="sql-constructor__fieldset-where">
            <FieldsetWhere prefix={this.props.prefix}/>
          </div>
          <div>
            <a className="btn btn-primary btn-xs" onClick={this.handleToggleVisibility}>
              <span className="glyphicon glyphicon-menu-up"></span> Закрыть конструктор
            </a>
          </div>
          <input type="hidden" defaultValue={JSON.stringify(this.props.sqlConstructor)} name={this.makeFullName('sqlConstructor', this.props.prefix)}/>
        </div>
      )
      : (
        <div className="sql-constructor sql-constructor--collapsed">
          <a className="btn btn-primary btn-xs" onClick={this.handleToggleVisibility}>
            <span className="glyphicon glyphicon-menu-down"></span> Контруктор запроса
          </a>
        </div>
      );
  }
}

const mapStateToProps = (state) => {
  return {
    sqlConstructor: mapStateToQuery(state)
  };
};

export default connect(mapStateToProps)(SqlContructor);
