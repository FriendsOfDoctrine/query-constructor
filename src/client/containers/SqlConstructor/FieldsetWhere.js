import { connect, Provider } from 'react-redux'
import WhereItem from './WhereItem'
import FieldsetWhere from '../../components/FieldsetWhere'
import { addWhere, removeWhere } from '../../redux/modules/where'
import { bindAllTo } from '../../util/helpers'

const mapStateToProps = (state, ownProps) => {
  return {
    whereCollection: state.where.items,
    canAddItem: !!Object.keys(state.where.entities).length,
    WhereItem: WhereItem,
  };
};

const mapDispatchToProps = {
  add: addWhere,
  remove: removeWhere,
};

export default connect(
  mapStateToProps,
  mapDispatchToProps
)(FieldsetWhere);
